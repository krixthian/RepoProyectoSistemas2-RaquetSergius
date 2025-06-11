<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ClienteService;
use App\Services\ZumbaService; // Asegúrate de tener este servicio
use App\Models\ClaseZumba;
use App\Models\AreaZumba;
use App\Models\Cliente;
use App\Http\Controllers\Chatbot\whatsappController; // Para la descarga de imagen
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str;
use App\Models\InscripcionClase; // Asegúrate de tener este modelo

class InscribirClaseZumbaHandler implements IntentHandlerInterface
{
    protected ClienteService $clienteService;
    protected ZumbaService $zumbaService;

    private const CACHE_TTL_MINUTES = 20;
    private const CACHE_KEY_PREFIX = 'inscribir_zumba_';
    private const MAX_DIAS_ANTICIPACION_INSCRIPCION = 7;

    private array $flowData = [
        'step' => 'inicio', // Pasos: inicio, esperando_seleccion_clases, esperando_confirmacion, esperando_comprobante, finalizado
        'clases_para_inscribir' => [], // [id_clase => datos_clase_con_fecha_calculada]
        'monto_total_pendiente' => 0,
        'ids_inscripciones_pendientes' => [],
        'user_profile_name' => null,
    ];
    private string $senderId;
    private string $cacheKey;

    public function __construct(ClienteService $clienteService, ZumbaService $zumbaService)
    {
        $this->clienteService = $clienteService;
        $this->zumbaService = $zumbaService;
    }

    private function loadFlowData(string $normalizedSenderId): void
    {
        $this->senderId = $normalizedSenderId;
        $this->cacheKey = self::CACHE_KEY_PREFIX . $this->senderId;
        $cachedData = Cache::get($this->cacheKey);
        $defaultData = [
            'step' => 'inicio',
            'clases_para_inscribir' => [],
            'monto_total_pendiente' => 0,
            'ids_inscripciones_pendientes' => [],
            'user_profile_name' => null,
        ];
        $this->flowData = $cachedData ? array_merge($defaultData, $cachedData) : $defaultData;
    }
    private function saveFlowData(): void
    {
        Cache::put($this->cacheKey, $this->flowData, now()->addMinutes(self::CACHE_TTL_MINUTES));
    }
    private function clearFlowData(bool $preserveProfileName = true): void
    {
        $userProfileName = $preserveProfileName ? $this->flowData['user_profile_name'] : null;
        Cache::forget($this->cacheKey);
        $this->flowData = ['step' => 'inicio', 'clases_para_inscribir' => [], 'monto_total_pendiente' => 0, 'ids_inscripciones_pendientes' => [], 'user_profile_name' => $userProfileName];
    }

    public function handle(array $parameters, string $normalizedSenderId, ?string $action = null): array
    {
        $this->loadFlowData($normalizedSenderId);
        Log::debug("[{$this->cacheKey}] InscribirClaseZumbaHandler. Action: {$action}. Step: {$this->flowData['step']}.");
        if (isset($parameters['user_profile_name']) && empty($this->flowData['user_profile_name'])) {
            $this->flowData['user_profile_name'] = $parameters['user_profile_name'];
        }

        $response = $this->gestionarFlujoInscripcion($action, $parameters);
        $this->saveFlowData();
        return $response;
    }

    private function gestionarFlujoInscripcion(?string $action, array $params): array
    {
        // --- ACCIONES DIRECTAS Y DE INTERRUPCIÓN ---
        if ($action === 'zumba.inscripcion.cancelar_proceso') {
            $this->clearFlowData();
            $this->flowData['step'] = 'finalizado';
            return $this->prepararRespuesta("Ok, he cancelado el proceso de inscripción a Zumba.", $this->generarNombresContextosParaLimpiar(['zumba_inscripcion_en_progreso']));
        }
        if ($action === 'zumba.inscripcion.enviarComprobante' && $this->flowData['step'] === 'esperando_comprobante') {
            return $this->manejarComprobante($params);
        }

        // --- PASO 1: INICIAR Y MOSTRAR HORARIOS ---
        if ($action === 'zumba.inscripcion.iniciar' || $this->flowData['step'] === 'inicio') {
            $this->clearFlowData();
            $this->flowData['step'] = 'mostrando_horarios';

            $messagesToSend = [];
            $areaZumba = AreaZumba::where('disponible', true)->first();
            $imageUrl = null;
            $captionHorarios = "¡Hola! Estos son nuestros horarios de Zumba. Cada clase tiene un ID para que puedas inscribirte.\n";

            if ($areaZumba && !empty($areaZumba->ruta_imagen) && file_exists(public_path($areaZumba->ruta_imagen))) {
                $imageUrl = URL::asset($areaZumba->ruta_imagen);
            }
            if (!$imageUrl && file_exists(public_path('image/horarios_zumba.jpg'))) {
                $imageUrl = URL::asset('image/horarios_zumba.jpg');
            }

            if ($imageUrl) {
                $messagesToSend[] = ['fulfillmentText' => $captionHorarios, 'message_type' => 'image', 'payload' => ['image_url' => $imageUrl, 'caption' => $captionHorarios]];
                $messagesToSend[] = ['fulfillmentText' => "¿A qué clases deseas inscribirte? Por favor, indícame los IDs (ej: 1, 3, 6).", 'message_type' => 'text'];
            } else {
                Log::warning("[InscribirClaseZumbaHandler] No se encontró ninguna imagen de horarios.");
                $messagesToSend[] = ['fulfillmentText' => "No pude cargar la imagen de horarios, pero puedes consultarlos en nuestras redes. Cuando sepas los IDs, envíamelos para inscribirte.", 'message_type' => 'text'];
            }

            $this->flowData['step'] = 'esperando_seleccion_clases';
            return $this->prepararRespuestaConMultiplesMensajes($messagesToSend, $this->generarNombresContextos(['zumba_inscripcion_esperando_seleccion_clases']));
        }

        // --- PASO 2: SELECCIONAR CLASES Y PEDIR CONFIRMACIÓN ---
        if ($action === 'zumba.inscripcion.seleccionarClases' && $this->flowData['step'] === 'esperando_seleccion_clases') {
            $claseIdsSeleccionadas = $this->parsearIdsDeParametros($params);
            if (empty($claseIdsSeleccionadas)) {
                return $this->prepararRespuesta("No entendí los IDs de las clases. Por favor, indícalos separados por comas o espacios (ej: 1, 2).", $this->generarNombresContextos(['zumba_inscripcion_esperando_seleccion_clases']));
            }

            $cliente = $this->clienteService->findOrCreateByTelefono($this->senderId, ['nombre_perfil_whatsapp' => $this->flowData['user_profile_name']])['cliente'];
            if (!$cliente) {
                return $this->prepararRespuesta("No pude identificarte. Por favor, intenta de nuevo.", $this->generarNombresContextosParaLimpiar(['zumba_inscripcion_en_progreso']));
            }

            list($clasesValidas, $mensajesValidacion) = $this->validarClasesSeleccionadas($claseIdsSeleccionadas, $cliente);

            $mensajeRespuesta = !empty($mensajesValidacion) ? implode("\n", $mensajesValidacion) . "\n\n" : "";

            if (empty($clasesValidas)) {
                $mensajeRespuesta .= "No hay clases válidas para inscribir. ¿Quieres seleccionar otras o escribir 'cancelar'?";
                return $this->prepararRespuesta($mensajeRespuesta, $this->generarNombresContextos(['zumba_inscripcion_esperando_seleccion_clases']));
            }

            $this->flowData['clases_para_inscribir'] = $clasesValidas;
            $this->flowData['step'] = 'esperando_confirmacion';
            $confirmMsg = "Confirmación de inscripción:\n";
            $montoTotal = 0;
            foreach ($clasesValidas as $claseInfo) {
                $fechaConfirm = Carbon::parse($claseInfo['fecha_calculada'])->locale('es')->isoFormat('dddd D [de] MMMM');
                $confirmMsg .= "- Clase ID {$claseInfo['id']}: {$fechaConfirm} a las {$claseInfo['hora_inicio']} (Bs. {$claseInfo['precio']})\n";
                $montoTotal += (float) $claseInfo['precio'];
            }
            $confirmMsg .= "*Monto total a pagar: Bs. " . number_format($montoTotal, 2) . "*\n¿Confirmas tu inscripción?";
            $mensajeRespuesta .= $confirmMsg; // Añadir la confirmación al mensaje de respuesta

            $payload = ['buttons' => [['id' => 'si_confirmar_inscripcion_zumba', 'title' => 'Sí, confirmar'], ['id' => 'cancelar_inscripcion_zumba', 'title' => 'No, cancelar']]];
            return $this->prepararRespuesta($mensajeRespuesta, $this->generarNombresContextos(['zumba_inscripcion_esperando_confirmacion']), 'interactive_buttons', $payload);
        }

        // --- PASO 3: CONFIRMAR INSCRIPCIÓN Y PROCEDER AL PAGO ---
        if ($action === 'zumba.inscripcion.confirmarSi' && $this->flowData['step'] === 'esperando_confirmacion') {
            return $this->iniciarFlujoDePago();
        }
        if ($action === 'zumba.inscripcion.confirmarNo' && $this->flowData['step'] === 'esperando_confirmacion') {
            $this->clearFlowData();
            $this->flowData['step'] = 'finalizado';
            return $this->prepararRespuesta("Ok, tu inscripción ha sido cancelada.", $this->generarNombresContextosParaLimpiar(['zumba_inscripcion_en_progreso']));
        }

        // --- FALLBACK ---
        $this->clearFlowData();
        return $this->prepararRespuesta("Hubo un problema con la inscripción a Zumba. ¿Empezamos de nuevo?", $this->generarNombresContextosParaLimpiar(['zumba_inscripcion_en_progreso']));
    }

    private function iniciarFlujoDePago(): array
    {
        if (empty($this->flowData['clases_para_inscribir'])) {
            return $this->prepararRespuesta("No había clases seleccionadas para confirmar. Proceso reiniciado.", $this->generarNombresContextosParaLimpiar(['zumba_inscripcion_en_progreso']));
        }
        $cliente = $this->clienteService->findOrCreateByTelefono($this->senderId, ['nombre_perfil_whatsapp' => $this->flowData['user_profile_name']])['cliente'];
        if (!$cliente) {
            return $this->prepararRespuesta("Error al identificarte.", $this->generarNombresContextosParaLimpiar(['zumba_inscripcion_en_progreso']));
        }

        $this->flowData['ids_inscripciones_pendientes'] = [];
        $montoTotal = 0;
        foreach ($this->flowData['clases_para_inscribir'] as $claseId => $claseInfo) {
            $resultado = $this->zumbaService->inscribirClienteAClasePorId($this->senderId, $claseId, $claseInfo['fecha_calculada'], ['nombre' => $cliente->nombre]);
            if ($resultado['success'] && isset($resultado['inscripcion'])) {
                $this->flowData['ids_inscripciones_pendientes'][] = $resultado['inscripcion']->inscripcion_id;
                $montoTotal += (float) ($resultado['inscripcion']->monto_pagado ?? 0);
            } else {
                Log::warning("[{$this->cacheKey}] Falló pre-inscripción para clase ID {$claseId}: " . $resultado['message']);
            }
        }

        if (empty($this->flowData['ids_inscripciones_pendientes'])) {
            $this->clearFlowData();
            return $this->prepararRespuesta("No se pudo registrar tu solicitud para ninguna de las clases seleccionadas. Por favor, intenta de nuevo.");
        }

        $this->flowData['monto_total_pendiente'] = $montoTotal;
        $this->flowData['step'] = 'esperando_comprobante';

        $mensajes = [];
        $urlQrEstatico = URL::asset('image/qr_pago_club.png');
        $captionQr = "¡Tus solicitudes están registradas!\nPara confirmarlas, realiza el pago de *Bs. " . number_format($montoTotal, 2) . "* escaneando este QR.";
        $mensajes[] = ['fulfillmentText' => $captionQr, 'message_type' => 'image', 'payload' => ['image_url' => $urlQrEstatico, 'caption' => $captionQr]];
        $instrucciones = "Una vez realizado el pago, por favor *envíame la foto o captura de pantalla de tu comprobante*.";
        $mensajes[] = ['fulfillmentText' => $instrucciones, 'message_type' => 'text'];

        return $this->prepararRespuestaConMultiplesMensajes($mensajes, $this->generarNombresContextos(['zumba_inscripcion_esperando_comprobante']));
    }

    private function manejarComprobante(array $params): array
    {
        $mediaId = $params['media_id'] ?? null;
        if (!$mediaId) {
            return $this->prepararRespuesta("No recibí una imagen. Por favor, envía la foto de tu comprobante.", $this->generarNombresContextos(['zumba_inscripcion_esperando_comprobante']));
        }
        if (empty($this->flowData['ids_inscripciones_pendientes'])) {
            return $this->prepararRespuesta("No recuerdo qué inscripciones estábamos procesando. Por favor, inicia de nuevo.", $this->generarNombresContextosParaLimpiar(['zumba_inscripcion_en_progreso']));
        }

        $whatsappDownloader = app(whatsappController::class);
        $rutaGuardada = $whatsappDownloader->descargarImagenWhatsapp($mediaId, "zumba");

        if ($rutaGuardada) {
            foreach ($this->flowData['ids_inscripciones_pendientes'] as $inscripcionId) {
                InscripcionClase::where('inscripcion_id', $inscripcionId)->update(['ruta_comprobante_pago' => $rutaGuardada]);
            }
            $mensajeAgradecimiento = "¡Gracias! Hemos recibido tu comprobante por Bs. " . number_format((float) $this->flowData['monto_total_pendiente'], 2) . ".\nUn encargado lo verificará y te notificaremos la confirmación final de tu(s) clase(s).";
        } else {
            return $this->prepararRespuesta("Tuve problemas para descargar tu comprobante. Intenta enviarlo de nuevo.", $this->generarNombresContextos(['zumba_inscripcion_esperando_comprobante']));
        }
        $this->clearFlowData();
        return $this->prepararRespuesta($mensajeAgradecimiento, $this->generarNombresContextosParaLimpiar(['zumba_inscripcion_en_progreso']));
    }

    private function parsearIdsDeParametros(array $params): array
    {
        $claseIdsInputRaw = $params['clase_ids_lista'] ?? $params['any'] ?? ($params['queryResult']['queryText'] ?? null);
        $claseIds = [];
        if ($claseIdsInputRaw) {
            if (is_array($claseIdsInputRaw)) {
                return $claseIdsInputRaw;
            }
            if (is_string($claseIdsInputRaw) && preg_match_all('/\d+/', $claseIdsInputRaw, $matches)) {
                return $matches[0];
            }
        }
        return [];
    }

    private function validarClasesSeleccionadas(array $ids, Cliente $cliente): array
    {
        $clasesValidas = [];
        $mensajesValidacion = [];
        $hoy = Carbon::today();
        $fechaLimite = $hoy->copy()->addDays(self::MAX_DIAS_ANTICIPACION_INSCRIPCION);

        foreach (array_unique($ids) as $idStr) {
            $id = trim($idStr);
            if (!is_numeric($id)) {
                continue;
            }
            $clase = ClaseZumba::find((int) $id);
            if (!$clase || !$clase->habilitado) {
                $mensajesValidacion[] = "Clase ID {$id}: No existe o no está habilitada.";
                continue;
            }

            $proximaFechaClase = null;
            for ($i = 0; $i <= self::MAX_DIAS_ANTICIPACION_INSCRIPCION; $i++) {
                $fechaIntento = $hoy->copy()->addDays($i);
                if (ucfirst($fechaIntento->locale('es_ES')->dayName) === $clase->diasemama) {
                    $fechaHoraClase = Carbon::parse($fechaIntento->toDateString() . ' ' . $clase->hora_inicio->format('H:i:s'));
                    if ($fechaHoraClase->isFuture()) {
                        $proximaFechaClase = $fechaHoraClase;
                        break;
                    }
                }
            }

            if (!$proximaFechaClase) {
                $mensajesValidacion[] = "Clase ID {$id}: No tiene una fecha futura disponible pronto.";
                continue;
            }

            $yaInscrito = InscripcionClase::where('cliente_id', $cliente->cliente_id)
                ->where('clase_id', $clase->clase_id)
                ->where('fecha_clase', $proximaFechaClase->toDateString())
                ->whereIn('estado', ['Activa', 'Pendiente'])->exists();
            if ($yaInscrito) {
                $mensajesValidacion[] = "Clase ID {$id}: Ya tienes una inscripción para el " . $proximaFechaClase->isoFormat('D MMM') . ".";
                continue;
            }

            $clasesValidas[$clase->clase_id] = [
                'id' => $clase->clase_id,
                'diasemama' => $clase->diasemama,
                'hora_inicio' => $clase->hora_inicio->format('H:i'),
                'hora_fin' => $clase->hora_fin->format('H:i'),
                'precio' => $clase->precio,
                'fecha_calculada' => $proximaFechaClase->toDateString(),
            ];
        }
        return [$clasesValidas, $mensajesValidacion];
    }

    private function prepararRespuesta(string $text, array $ctx = [], string $type = 'text', array $pl = []): array
    {
        return ['messages_to_send' => [['fulfillmentText' => $text, 'message_type' => $type, 'payload' => $pl]], 'outputContextsToSetActive' => $ctx];
    }
    private function prepararRespuestaConMultiplesMensajes(array $messages, array $ctx = []): array
    {
        return ['messages_to_send' => $messages, 'outputContextsToSetActive' => $ctx];
    }
    private function generarNombresContextos(array $names, string $flow = 'zumba_inscripcion_en_progreso'): array
    { /* ... */
        return [];
    }
    private function generarNombresContextosParaLimpiar(array $names, string $flow = 'zumba_inscripcion_en_progreso'): array
    { /* ... */
        return [];
    }
}