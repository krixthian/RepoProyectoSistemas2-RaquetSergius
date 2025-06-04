<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ClienteService;
use App\Services\ZumbaService;
use App\Models\ClaseZumba;
use App\Models\AreaZumba; // Para la imagen de horarios
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL; // Para la URL de la imagen
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;


class InscribirClaseZumbaHandler implements IntentHandlerInterface
{
    protected ClienteService $clienteService;
    protected ZumbaService $zumbaService;

    public const CACHE_TTL_MINUTES = 20;
    public const CACHE_KEY_PREFIX = 'inscribir_zumba_';
    public const MAX_DIAS_ANTICIPACION_INSCRIPCION = 6;

    private array $flowData = [
        'current_flow' => 'inscripcion_zumba',
        'step' => 'inicio', // inicio, esperando_seleccion_clases, esperando_confirmacion_final, finalizado
        'clases_disponibles_mostradas_con_id' => [], // [id_clase => datos_clase_con_proxima_fecha]
        'clases_para_inscribir_confirmacion' => [], // [id_clase => datos_clase_con_proxima_fecha]
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
            'current_flow' => 'inscripcion_zumba',
            'step' => 'inicio',
            'clases_disponibles_mostradas_con_id' => [],
            'clases_para_inscribir_confirmacion' => [],
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
        $this->flowData = [ /* ... defaultData ... */];
        $this->flowData['user_profile_name'] = $userProfileName;
    }

    public function handle(array $parameters, string $normalizedSenderId, ?string $action = null): array
    {
        $this->loadFlowData($normalizedSenderId);
        Log::debug("[{$this->cacheKey}] InscribirClaseZumbaHandler. Action: {$action}. Step: {$this->flowData['step']}. Params:", $parameters);
        if (isset($parameters['user_profile_name']) && empty($this->flowData['user_profile_name'])) {
            $this->flowData['user_profile_name'] = $parameters['user_profile_name'];
        }

        $response = $this->gestionarFlujoInscripcion($action, $parameters);
        $this->saveFlowData();
        return $response;
    }

    private function gestionarFlujoInscripcion(?string $action, array $currentDialogflowParams): array
    {
        // --- ACCIONES DIRECTAS ---
        if ($action === 'zumba.inscripcion.cancelar_proceso') {
            $this->clearFlowData();
            $this->flowData['step'] = 'finalizado';
            return $this->prepararRespuesta("Ok, he cancelado el proceso de inscripción a Zumba.", $this->generarNombresContextosParaLimpiar(['zumba_inscripcion_en_progreso']));
        }

        // --- PASO 1: INICIAR Y MOSTRAR HORARIOS/PEDIR IDs ---
        if ($action === 'zumba.inscripcion.iniciar' || $this->flowData['step'] === 'inicio') {
            $this->clearFlowData();
            $this->flowData['step'] = 'mostrando_horarios'; // Nuevo step intermedio

            $messagesToSend = [];
            $areaZumba = AreaZumba::where('disponible', true)->first(); // Asumiendo una sola área o la principal
            $imageUrl = null;
            $captionHorarios = "Estos son nuestros horarios de Zumba. Cada clase tiene un ID.\n";

            if ($areaZumba && !empty($areaZumba->ruta_imagen)) {
                if (file_exists(public_path($areaZumba->ruta_imagen))) {
                    $imageUrl = URL::asset($areaZumba->ruta_imagen);
                }
            }
            // Fallback a imagen genérica si no hay específica
            if (!$imageUrl) {
                $genericImagePublicPath = 'image/horarios_zumba.jpg';
                if (file_exists(public_path($genericImagePublicPath))) {
                    $imageUrl = URL::asset($genericImagePublicPath);
                } else {
                    Log::warning("[InscribirClaseZumbaHandler] No se encontró imagen de horarios específica ni genérica.");
                    $captionHorarios .= "(No pudimos cargar la imagen de horarios en este momento, pero puedes consultarlos con administracion).\n";
                }
            }

            if ($imageUrl) {
                $messagesToSend[] = [
                    'fulfillmentText' => $captionHorarios, // Usado como caption o texto principal
                    'message_type' => 'image',
                    'payload' => ['image_url' => $imageUrl, 'caption' => $captionHorarios]
                ];
                $messagesToSend[] = [ // Segundo mensaje para pedir los IDs
                    'fulfillmentText' => "¿A qué clases deseas inscribirte? Por favor, indícame los IDs de las clases separados por comas o espacios (ej: 1, 3, 6).",
                    'message_type' => 'text',
                    'payload' => []
                ];
            } else { // Si no hay imagen, solo texto
                $messagesToSend[] = [
                    'fulfillmentText' => $captionHorarios . "¿A qué clases deseas inscribirte? Por favor, indícame los IDs de las clases separados por comas o espacios (ej: 1, 3, 6). (Consulta nuestros horarios para ver los IDs)",
                    'message_type' => 'text',
                    'payload' => []
                ];
            }

            $this->flowData['step'] = 'esperando_seleccion_clases';
            return $this->prepararRespuestaConMultiplesMensajes($messagesToSend, $this->generarNombresContextos(['zumba_inscripcion_esperando_seleccion_clases']));
        }

        // --- PASO 2: SELECCIONAR CLASES Y PEDIR CONFIRMACIÓN ---
        if ($action === 'zumba.inscripcion.seleccionarClases' && $this->flowData['step'] === 'esperando_seleccion_clases') {
            $claseIdsInputRaw = $currentDialogflowParams['clase_ids_lista'] ?? $currentDialogflowParams['any'] ?? ($currentDialogflowParams['queryResult']['queryText'] ?? null);
            $claseIdsSeleccionadas = [];

            if ($claseIdsInputRaw) {
                if (is_array($claseIdsInputRaw)) { // Si Dialogflow ya lo devuelve como lista (ideal)
                    $claseIdsSeleccionadas = $claseIdsInputRaw;
                } elseif (is_string($claseIdsInputRaw)) {
                    if (preg_match_all('/\d+/', $claseIdsInputRaw, $matches)) {
                        $claseIdsSeleccionadas = $matches[0];
                    }
                }
            }

            if (empty($claseIdsSeleccionadas)) {
                return $this->prepararRespuesta("No entendí los IDs de las clases. Por favor, indícalos separados por comas o espacios (ej: 1, 2).", $this->generarNombresContextos(['zumba_inscripcion_esperando_seleccion_clases']));
            }

            $cliente = $this->clienteService->findOrCreateByTelefono($this->senderId, ['nombre_perfil_whatsapp' => $this->flowData['user_profile_name']])['cliente'];
            if (!$cliente) {
                return $this->prepararRespuesta("No pude identificarte. Intenta de nuevo.", $this->generarNombresContextosParaLimpiar(['zumba_inscripcion_en_progreso']));
            }

            $clasesParaConfirmar = [];
            $mensajesValidacionClases = [];
            $montoTotal = 0;
            $hoy = Carbon::today();
            $fechaLimite = $hoy->copy()->addDays(self::MAX_DIAS_ANTICIPACION_INSCRIPCION);

            foreach ($claseIdsSeleccionadas as $idStr) {
                $id = trim($idStr);
                if (!is_numeric($id)) {
                    $mensajesValidacionClases[] = "ID '{$idStr}' no es un número válido.";
                    continue;
                }
                $clase = ClaseZumba::where('clase_id', (int) $id)->where('habilitado', true)->first();
                if (!$clase) {
                    $mensajesValidacionClases[] = "Clase con ID {$id} no existe o no está habilitada.";
                    continue;
                }

                // Calcular la próxima fecha para esta clase (lógica de ejemplo)
                $proximaFechaClase = null;
                for ($i = 0; $i <= self::MAX_DIAS_ANTICIPACION_INSCRIPCION; $i++) {
                    $fechaIntento = $hoy->copy()->addDays($i);
                    if (ucfirst($fechaIntento->locale('es_ES')->dayName) === $clase->diasemama) {
                        $fechaHoraClase = Carbon::parse($fechaIntento->toDateString() . ' ' . $clase->hora_inicio->format('H:i:s'));
                        if ($fechaHoraClase->isFuture()) { // Asegurar que sea futura o hoy más tarde
                            $proximaFechaClase = $fechaHoraClase;
                            break;
                        }
                    }
                }

                if (!$proximaFechaClase) {
                    $mensajesValidacionClases[] = "No se encontró una fecha futura disponible para la clase ID {$id} ({$clase->diasemama}) en los próximos " . self::MAX_DIAS_ANTICIPACION_INSCRIPCION . " días.";
                    continue;
                }
                if ($proximaFechaClase->gt($fechaLimite)) {
                    $mensajesValidacionClases[] = "La clase ID {$id} ({$clase->diasemama} el " . $proximaFechaClase->isoFormat('D MMM') . ") excede el límite de inscripción de " . self::MAX_DIAS_ANTICIPACION_INSCRIPCION . " días.";
                    continue;
                }

                // Aquí puedes añadir más validaciones: si ya está inscrito para esa fecha, si hay cupo.
                // $puedeInscribirse = $this->zumbaService->verificarDisponibilidadInscripcion($cliente->cliente_id, $clase->clase_id, $proximaFechaClase->toDateString());
                // if (!$puedeInscribirse['status']) {
                //    $mensajesValidacionClases[] = "Clase ID {$id}: " . $puedeInscribirse['message'];
                //    continue;
                // }

                $clasesParaConfirmar[$clase->clase_id] = [
                    'id' => $clase->clase_id,
                    'diasemama' => $clase->diasemama,
                    'hora_inicio' => $clase->hora_inicio->format('H:i'),
                    'hora_fin' => $clase->hora_fin->format('H:i'),
                    'precio' => $clase->precio,
                    'fecha_calculada' => $proximaFechaClase->toDateString(),
                    'instructor' => $clase->instructor->nombre ?? 'N/A',
                ];
                $montoTotal += (float) $clase->precio;
            }

            if (!empty($mensajesValidacionClases)) {
                $mensajeError = implode("\n", $mensajesValidacionClases);
                $mensajeError .= "\n\nPor favor, revisa los IDs e inténtalo de nuevo, o escribe 'cancelar' para salir.";
                return $this->prepararRespuesta($mensajeError, $this->generarNombresContextos(['zumba_inscripcion_esperando_seleccion_clases']));
            }

            if (empty($clasesParaConfirmar)) {
                return $this->prepararRespuesta("No seleccionaste ninguna clase válida. ¿Quieres intentarlo de nuevo o ver los horarios?", $this->generarNombresContextos(['zumba_inscripcion_esperando_seleccion_clases']));
            }

            $this->flowData['clases_para_inscribir_confirmacion'] = $clasesParaConfirmar;
            $confirmMsg = "Confirmación de inscripción:\n";
            foreach ($clasesParaConfirmar as $claseInfo) {
                $fechaConfirm = Carbon::parse($claseInfo['fecha_calculada'])->locale('es')->isoFormat('dddd D [de] MMMM');
                $confirmMsg .= "- Clase ID {$claseInfo['id']}: {$claseInfo['diasemama']} ({$fechaConfirm}) de {$claseInfo['hora_inicio']} a {$claseInfo['hora_fin']} (Bs. {$claseInfo['precio']})\n";
            }
            $confirmMsg .= "Monto total a pagar: Bs. " . number_format($montoTotal, 2) . "\n¿Confirmas tu inscripción a esta(s) clase(s)?";
            $this->flowData['step'] = 'esperando_confirmacion_final';
            $payload = ['buttons' => [['id' => 'confirmar_inscripcion_zumba', 'title' => 'Sí, confirmar'], ['id' => 'cancelar_inscripcion_zumba', 'title' => 'No, cancelar']]];
            return $this->prepararRespuesta($confirmMsg, $this->generarNombresContextos(['zumba_inscripcion_esperando_confirmacion_final']), 'interactive_buttons', $payload);
        }

        // --- PASO 3: CONFIRMAR INSCRIPCIÓN FINAL ---
        if ($action === 'zumba.inscripcion.confirmarSi' && $this->flowData['step'] === 'esperando_confirmacion_final') {
            if (empty($this->flowData['clases_para_inscribir_confirmacion'])) {
                $this->clearFlowData();
                return $this->prepararRespuesta("No había clases seleccionadas para confirmar. Proceso reiniciado.", $this->generarNombresContextosParaLimpiar(['zumba_inscripcion_en_progreso']));
            }
            $respuestasInscripcion = [];
            $cliente = $this->clienteService->findOrCreateByTelefono($this->senderId, ['nombre_perfil_whatsapp' => $this->flowData['user_profile_name']])['cliente'];
            if (!$cliente) { /* ... error ... */
                $this->clearFlowData();
                return $this->prepararRespuesta("Error al identificarte para finalizar la inscripción.", $this->generarNombresContextosParaLimpiar(['zumba_inscripcion_en_progreso']));
            }


            foreach ($this->flowData['clases_para_inscribir_confirmacion'] as $claseId => $claseInfo) {
                $resultado = $this->zumbaService->inscribirClienteAClasePorId(
                    $this->senderId, // Ya normalizado
                    (int) $claseId,
                    $claseInfo['fecha_calculada'],
                    ['nombre_perfil_whatsapp' => $this->flowData['user_profile_name']] // Para creación de cliente si es necesario
                );
                $respuestasInscripcion[] = "Clase ID {$claseId} ({$claseInfo['diasemama']} {$claseInfo['hora_inicio']}): " . $resultado['message'];
            }
            $this->clearFlowData();
            $this->flowData['step'] = 'finalizado';
            $mensajeFinal = implode("\n", $respuestasInscripcion);
            $mensajeFinal .= "\n\n¡Gracias! ¿Puedo ayudarte con algo más?";
            return $this->prepararRespuesta($mensajeFinal, $this->generarNombresContextosParaLimpiar(['zumba_inscripcion_en_progreso']));
        }
        if ($action === 'zumba.inscripcion.confirmarNo' && $this->flowData['step'] === 'esperando_confirmacion_final') {
            $this->clearFlowData();
            $this->flowData['step'] = 'finalizado';
            return $this->prepararRespuesta("Ok, tu inscripción a las clases ha sido cancelada. Puedes volver a intentarlo o consultar el menú.", $this->generarNombresContextosParaLimpiar(['zumba_inscripcion_en_progreso']));
        }

        Log::warning("[{$this->cacheKey}] Fallback en InscribirClaseZumba. Step: {$this->flowData['step']}, Action: {$action}");
        $this->clearFlowData();
        return $this->prepararRespuesta("Hubo un problema con la inscripción a Zumba. ¿Empezamos de nuevo o 'menú'?", $this->generarNombresContextosParaLimpiar(['zumba_inscripcion_en_progreso']));
    }

    private function prepararRespuesta(string|array $fulfillmentTextOrMessages, array $outputContextsToSetActive = [], string $messageType = 'text', array $payload = []): array
    {
        $messages = [];
        if (is_string($fulfillmentTextOrMessages)) {
            $messages[] = ['fulfillmentText' => $fulfillmentTextOrMessages, 'message_type' => $messageType, 'payload' => $payload];
        } elseif (is_array($fulfillmentTextOrMessages) && !empty($fulfillmentTextOrMessages) && isset($fulfillmentTextOrMessages[0]['fulfillmentText'])) {
            $messages = $fulfillmentTextOrMessages; // Asume que ya es un array de mensajes
        } else { // Fallback si la estructura no es la esperada
            $messages[] = ['fulfillmentText' => "Se produjo un error al preparar la respuesta.", 'message_type' => 'text', 'payload' => []];
        }
        return ['messages_to_send' => $messages, 'outputContextsToSetActive' => $outputContextsToSetActive];
    }
    // Wrapper para cuando se quiere enviar un solo mensaje, para mantener consistencia con el return type
    private function prepararRespuestaUnSoloMensaje(string $fulfillmentText, array $outputContextsToSetActive = [], string $messageType = 'text', array $payload = []): array
    {
        return $this->prepararRespuesta(
            [['fulfillmentText' => $fulfillmentText, 'message_type' => $messageType, 'payload' => $payload]],
            $outputContextsToSetActive
        );
    }
    // Necesitas una función prepararRespuestaConMultiplesMensajes si un paso devuelve varios mensajes
    private function prepararRespuestaConMultiplesMensajes(array $mensajesArray, array $outputContextsToSetActive = []): array
    {
        return [
            'messages_to_send' => $mensajesArray,
            'outputContextsToSetActive' => $outputContextsToSetActive
        ];
    }

    private function generarNombresContextos(array $specificContextNamesAsLifespanArray, string $flowContext = 'zumba_inscripcion_en_progreso'): array
    {
        $projectId = trim(config('dialogflow.project_id'), '/');
        $sessionId = 'whatsapp-' . $this->senderId;
        $contexts = [];
        if (!$projectId) {
            Log::error("[InscribirClaseZumbaHandler] Project ID vacío en generarNombresContextos.");
            return [];
        }

        if ($flowContext && $this->flowData['step'] !== 'inicio' && $this->flowData['step'] !== 'finalizado') {
            $contexts[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/{$flowContext}", 'lifespanCount' => 5];
        }
        foreach ($specificContextNamesAsLifespanArray as $name => $lifespan) {
            if (is_int($name)) { // Si es un array simple de nombres de contexto ['contexto1', 'contexto2']
                $name = $lifespan; // El valor es el nombre del contexto
                $lifespan = 2;   // Lifespan por defecto para contextos de paso
            }
            $contexts[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/" . trim($name, '/'), 'lifespanCount' => $lifespan];
        }
        return $contexts;
    }
    private function generarNombresContextosParaLimpiar(array $contextNamesToClear, string $flowContext = 'zumba_inscripcion_en_progreso'): array
    {
        $projectId = trim(config('dialogflow.project_id'), '/');
        $sessionId = 'whatsapp-' . $this->senderId;
        $contexts = [];
        if (!$projectId) {
            return [];
        }
        if ($flowContext) {
            $contexts[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/{$flowContext}", 'lifespanCount' => 0];
        }
        foreach ($contextNamesToClear as $name) {
            $contexts[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/" . trim($name, '/'), 'lifespanCount' => 0];
        }
        return $contexts;
    }

}