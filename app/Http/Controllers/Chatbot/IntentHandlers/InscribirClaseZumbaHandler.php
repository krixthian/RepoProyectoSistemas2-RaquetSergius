<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ClienteService;
use App\Services\ZumbaService; // Necesitarás crear este servicio
use App\Models\ClaseZumba;    // Para consultar clases
use App\Models\Cliente;        // Para obtener el cliente
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use InvalidArgumentException;
use Exception;

class InscribirClaseZumbaHandler implements IntentHandlerInterface
{
    protected ClienteService $clienteService;
    protected ZumbaService $zumbaService;

    private const CACHE_TTL_MINUTES = 20;
    private const CACHE_KEY_PREFIX = 'inscribir_zumba_';
    private const MAX_DIAS_ANTICIPACION_INSCRIPCION = 7;

    private array $flowData = [
        'current_flow' => 'inscripcion_zumba',
        'step' => 'inicio', // inicio, esperando_fecha, esperando_seleccion_clases, esperando_confirmacion, finalizado
        'fecha_seleccionada' => null,
        'clases_disponibles_mostradas' => [], // [id_clase => datos_clase]
        'clases_para_inscribir_confirmacion' => [], // [id_clase]
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
    { /* ... (similar a ReservaCanchaOrquestadorHandler) ... */
        $this->senderId = $normalizedSenderId;
        $this->cacheKey = self::CACHE_KEY_PREFIX . $this->senderId;
        $cachedData = Cache::get($this->cacheKey);
        $defaultData = [
            'current_flow' => 'inscripcion_zumba',
            'step' => 'inicio',
            'fecha_seleccionada' => null,
            'clases_disponibles_mostradas' => [],
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

        // Procesar parámetros entrantes antes de gestionar el flujo
        if (isset($parameters['fecha'])) {
            try {
                $this->flowData['fecha_seleccionada'] = Carbon::parse($parameters['fecha'])->toDateString();
            } catch (InvalidFormatException $e) {
                Log::warning("[{$this->cacheKey}] Fecha inválida de DF: " . $parameters['fecha']);
                $this->flowData['fecha_seleccionada'] = null;
            }
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

        // --- PASO 1: INICIAR Y PEDIR FECHA ---
        if ($action === 'zumba.inscripcion.iniciar' || $this->flowData['step'] === 'inicio') {
            $this->clearFlowData(); // Reiniciar
            $this->flowData['step'] = 'esperando_fecha';
            $mensaje = "¿Para qué fecha te gustaría ver las clases de Zumba disponibles? Puedes inscribirte con hasta " . self::MAX_DIAS_ANTICIPACION_INSCRIPCION . " días de antelación.";
            return $this->prepararRespuesta($mensaje, $this->generarNombresContextos(['zumba_inscripcion_esperando_fecha']));
        }

        // --- PASO 2: PROPORCIONAR FECHA Y MOSTRAR CLASES ---
        if ($action === 'zumba.inscripcion.proporcionarFecha' || ($this->flowData['step'] === 'esperando_fecha' && $this->flowData['fecha_seleccionada'])) {
            if (!$this->flowData['fecha_seleccionada']) {
                $this->flowData['step'] = 'esperando_fecha';
                return $this->prepararRespuesta("No entendí la fecha. ¿Para qué día quieres ver las clases? (Máx. " . self::MAX_DIAS_ANTICIPACION_INSCRIPCION . " días).", $this->generarNombresContextos(['zumba_inscripcion_esperando_fecha']));
            }
            try {
                $fechaSeleccionada = Carbon::parse($this->flowData['fecha_seleccionada'])->startOfDay();
                $hoy = Carbon::today();
                $fechaLimite = $hoy->copy()->addDays(self::MAX_DIAS_ANTICIPACION_INSCRIPCION);
                if ($fechaSeleccionada->isPast() && !$fechaSeleccionada->isToday())
                    throw new InvalidArgumentException("Solo puedes ver clases de hoy o futuras.");
                if ($fechaSeleccionada->gt($fechaLimite))
                    throw new InvalidArgumentException("Solo puedes ver clases con " . self::MAX_DIAS_ANTICIPACION_INSCRIPCION . " días de anticipación (hasta el " . $fechaLimite->locale('es')->isoFormat('D MMM') . ").");
            } catch (Exception $e) {
                $this->flowData['fecha_seleccionada'] = null;
                $this->flowData['step'] = 'esperando_fecha';
                return $this->prepararRespuesta($e->getMessage() . " ¿Para qué otra fecha deseas ver?", $this->generarNombresContextos(['zumba_inscripcion_esperando_fecha']));
            }

            $diaSemanaNombre = ucfirst($fechaSeleccionada->locale('es_ES')->dayName); // 'Lunes', 'Martes', etc.
            $clasesDelDia = ClaseZumba::where('diasemama', $diaSemanaNombre)
                ->where('habilitado', true)
                ->with('instructor', 'area') // Cargar relaciones
                ->orderBy('hora_inicio')->get();

            if ($fechaSeleccionada->isToday()) { // Filtrar clases pasadas para hoy
                $clasesDelDia = $clasesDelDia->filter(function ($clase) {
                    return Carbon::parse($clase->hora_inicio)->isFuture();
                });
            }

            if ($clasesDelDia->isEmpty()) {
                $this->flowData['step'] = 'esperando_fecha'; // Volver a pedir fecha
                return $this->prepararRespuesta("No hay clases de Zumba disponibles para el " . $fechaSeleccionada->locale('es')->isoFormat('dddd D [de] MMMM') . ". ¿Quieres intentar otra fecha?", $this->generarNombresContextos(['zumba_inscripcion_esperando_fecha']));
            }

            $this->flowData['clases_disponibles_mostradas'] = [];
            $mensajeClases = "Clases de Zumba para el " . $fechaSeleccionada->locale('es')->isoFormat('dddd D [de] MMMM') . " (ID - Hora - Instructor - Precio):\n";
            foreach ($clasesDelDia as $clase) {
                $instructorNombre = $clase->instructor->nombre ?? 'N/A';
                $mensajeClases .= "*ID {$clase->clase_id}*: " . $clase->hora_inicio->format('H:i') . "-" . $clase->hora_fin->format('H:i') . " con {$instructorNombre} (Bs. {$clase->precio})\n";
                $this->flowData['clases_disponibles_mostradas'][(string) $clase->clase_id] = $clase->toArray(); // Guardar ID como string
            }
            $mensajeClases .= "\nPor favor, dime el ID o los IDs de las clases a las que quieres inscribirte (ej: '1' o '1, 2, 3'). Escribe 'ninguna' si no deseas inscribirte a estas.";
            $this->flowData['step'] = 'esperando_seleccion_clases';
            return $this->prepararRespuesta($mensajeClases, $this->generarNombresContextos(['zumba_inscripcion_esperando_seleccion_clases']));
        }

        // --- PASO 3: SELECCIONAR CLASES Y PEDIR CONFIRMACIÓN ---
        if ($action === 'zumba.inscripcion.seleccionarClases' && $this->flowData['step'] === 'esperando_seleccion_clases') {
            $claseIdsInput = $currentDialogflowParams['clase_ids'] ?? null; // Dialogflow debería dar una lista de strings/números
            if (is_string($claseIdsInput)) { // Si viene como string "1,2,3" o "1 2 3"
                if (preg_match_all('/\d+/', $claseIdsInput, $matches)) {
                    $claseIdsInput = $matches[0];
                } else {
                    $claseIdsInput = null;
                }
            }

            if (empty($claseIdsInput) || !is_array($claseIdsInput)) {
                return $this->prepararRespuesta("No entendí los IDs de las clases. Por favor, indícalos separados por comas o espacios (ej: 1, 2).", $this->generarNombresContextos(['zumba_inscripcion_esperando_seleccion_clases']));
            }

            $cliente = $this->clienteService->findOrCreateByTelefono($this->senderId, ['nombre_perfil_whatsapp' => $this->flowData['user_profile_name']])['cliente'];
            if (!$cliente) { /* ... error ... */
            }

            $clasesValidasParaInscribir = [];
            $mensajesValidacion = [];
            foreach ($claseIdsInput as $idStr) {
                $id = trim($idStr);
                if (!is_numeric($id) || !isset($this->flowData['clases_disponibles_mostradas'][$id])) {
                    $mensajesValidacion[] = "El ID de clase '{$id}' no es válido.";
                    continue;
                }
                // Aquí puedes añadir más validaciones: si ya está inscrito, si hay cupo (desde ZumbaService)
                // $puedeInscribirse = $this->zumbaService->verificarDisponibilidadInscripcion($cliente->cliente_id, (int)$id, $this->flowData['fecha_seleccionada']);
                // if (!$puedeInscribirse['status']) {
                //    $mensajesValidacion[] = "Clase ID {$id}: " . $puedeInscribirse['message'];
                //    continue;
                // }
                $clasesValidasParaInscribir[(int) $id] = $this->flowData['clases_disponibles_mostradas'][$id];
            }

            if (empty($clasesValidasParaInscribir)) {
                $mensajeError = implode("\n", $mensajesValidacion);
                $mensajeError .= "\nNo hay clases válidas para inscribir. ¿Quieres seleccionar otras o cambiar de fecha?";
                // Dejar step en esperando_seleccion_clases para reintentar con esta fecha o que el usuario cambie de idea
                return $this->prepararRespuesta($mensajeError, $this->generarNombresContextos(['zumba_inscripcion_esperando_seleccion_clases']));
            }

            $this->flowData['clases_para_inscribir_confirmacion'] = array_keys($clasesValidasParaInscribir);
            $confirmMsg = "Confirmación de inscripción para el " . Carbon::parse($this->flowData['fecha_seleccionada'])->locale('es')->isoFormat('dddd D MMM') . ":\n";
            $montoTotal = 0;
            foreach ($clasesValidasParaInscribir as $idClase => $claseInfo) {
                $horaInicio = Carbon::parse($claseInfo['hora_inicio'])->format('H:i');
                $confirmMsg .= "- Clase ID {$idClase} a las {$horaInicio} (Bs. {$claseInfo['precio']})\n";
                $montoTotal += $claseInfo['precio'];
            }
            $confirmMsg .= "Monto total: Bs. " . number_format($montoTotal, 2) . "\n¿Confirmas tu inscripción a esta(s) clase(s)?";
            $this->flowData['step'] = 'esperando_confirmacion_inscripcion';
            $payload = ['buttons' => [['id' => 'si_inscribir_zumba', 'title' => 'Sí, confirmar'], ['id' => 'no_inscribir_zumba', 'title' => 'No, cancelar']]];
            return $this->prepararRespuesta($confirmMsg, $this->generarNombresContextos(['zumba_inscripcion_esperando_confirmacion']), 'interactive_buttons', $payload);
        }

        // --- PASO 4: CONFIRMAR INSCRIPCIÓN ---
        if ($action === 'zumba.inscripcion.confirmarSi' && $this->flowData['step'] === 'esperando_confirmacion_inscripcion') {
            if (empty($this->flowData['clases_para_inscribir_confirmacion'])) {
                $this->clearFlowData();
                return $this->prepararRespuesta("No había clases seleccionadas para confirmar. Proceso reiniciado.", $this->generarNombresContextosParaLimpiar(['zumba_inscripcion_en_progreso']));
            }
            $respuestasInscripcion = [];
            $cliente = $this->clienteService->findOrCreateByTelefono($this->senderId, ['nombre_perfil_whatsapp' => $this->flowData['user_profile_name']])['cliente'];
            if (!$cliente) { /* ... error ... */
            }

            foreach ($this->flowData['clases_para_inscribir_confirmacion'] as $claseId) {
                // Llamar a la función de servicio que realmente inscribe
                $resultado = $this->zumbaService->inscribirClienteAClasePorId(
                    $this->senderId,
                    $claseId,
                    $this->flowData['fecha_seleccionada'],
                    ['nombre_perfil_whatsapp' => $this->flowData['user_profile_name']] // Para crear cliente si no existe
                );
                $respuestasInscripcion[] = $resultado['message'];
            }
            $this->clearFlowData();
            $this->flowData['step'] = 'finalizado';
            $mensajeFinal = implode("\n", $respuestasInscripcion);
            $mensajeFinal .= "\n\n¡Gracias! ¿Algo más?";
            return $this->prepararRespuesta($mensajeFinal, $this->generarNombresContextosParaLimpiar(['zumba_inscripcion_en_progreso']));
        }
        if ($action === 'zumba.inscripcion.confirmarNo' && $this->flowData['step'] === 'esperando_confirmacion_inscripcion') {
            $this->clearFlowData();
            $this->flowData['step'] = 'finalizado';
            return $this->prepararRespuesta("Ok, tu inscripción ha sido cancelada. Puedes volver a intentarlo o consultar el menú.", $this->generarNombresContextosParaLimpiar(['zumba_inscripcion_en_progreso']));
        }

        Log::warning("[{$this->cacheKey}] Fallback en InscribirClaseZumba. Step: {$this->flowData['step']}, Action: {$action}");
        $this->clearFlowData();
        return $this->prepararRespuesta("Hubo un problema con la inscripción a Zumba. Intentemos de nuevo desde el menú.", $this->generarNombresContextosParaLimpiar(['zumba_inscripcion_en_progreso']));
    }

    // Funciones auxiliares (prepararRespuesta, generarNombresContextos, etc., como en ReservaCanchaOrquestadorHandler)
    private function prepararRespuesta(string|array $fulfillmentTextOrMessages, array $outputContextsToSetActive = [], string $messageType = 'text', array $payload = []): array
    {
        $messages = [];
        if (is_string($fulfillmentTextOrMessages)) {
            $messages[] = ['fulfillmentText' => $fulfillmentTextOrMessages, 'message_type' => $messageType, 'payload' => $payload];
        } elseif (is_array($fulfillmentTextOrMessages) && isset($fulfillmentTextOrMessages[0]['fulfillmentText'])) { // Ya es un array de mensajes
            $messages = $fulfillmentTextOrMessages;
        }
        return ['messages_to_send' => $messages, 'outputContextsToSetActive' => $outputContextsToSetActive];
    }
    private function generarNombresContextos(array $specificContextNamesAsLifespanArray, string $flowContext = 'zumba_inscripcion_en_progreso'): array
    { /* ... similar a ReservaCanchaOrquestadorHandler ... */
        $projectId = trim(config('dialogflow.project_id'), '/');
        $sessionId = 'whatsapp-' . $this->senderId;
        $contexts = [];
        if (!$projectId) {
            Log::error("Project ID vacío en generarNombresContextos para Zumba.");
            return [];
        }

        if ($flowContext && $this->flowData['step'] !== 'inicio' && $this->flowData['step'] !== 'finalizado') {
            $contexts[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/{$flowContext}", 'lifespanCount' => 5];
        }
        foreach ($specificContextNamesAsLifespanArray as $name => $lifespan) {
            if (is_int($name)) { // Si es un array simple de nombres de contexto
                $name = $lifespan; // El valor es el nombre del contexto
                $lifespan = 2;   // Lifespan por defecto
            }
            $contexts[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/" . trim($name, '/'), 'lifespanCount' => $lifespan];
        }
        return $contexts;
    }
    private function generarNombresContextosParaLimpiar(array $contextNamesToClear, string $flowContext = 'zumba_inscripcion_en_progreso'): array
    { /* ... similar a ReservaCanchaOrquestadorHandler ... */
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