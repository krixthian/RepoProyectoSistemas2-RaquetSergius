<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ClienteService;
use App\Services\ZumbaService;
use App\Models\InscripcionClase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CancelarInscripcionZumbaHandler implements IntentHandlerInterface
{
    protected ClienteService $clienteService;
    protected ZumbaService $zumbaService;
    protected string $dialogflowProjectId;
    private const CACHE_KEY_PREFIX = 'cancelar_insc_zumba_';
    private const CACHE_TTL_MINUTES = 15;
    private const MIN_HORAS_ANTICIPACION_CANCELAR_CLASE = 2;

    private array $flowData = [
        'step' => 'inicio', // inicio, esperando_seleccion_inscripciones, esperando_confirmacion_final
        'inscripciones_cancelables_mostradas' => [], // [id_inscripcion => detalle_texto]
        'ids_inscripcion_para_confirmar_cancelacion' => [],
    ];
    private string $senderId;
    private string $cacheKey;

    public function __construct(ClienteService $clienteService, ZumbaService $zumbaService)
    {
        $this->clienteService = $clienteService;
        $this->zumbaService = $zumbaService;
        $this->dialogflowProjectId = trim(config('dialogflow.project_id'), '/'); // Intentar cargar aquí

        if (empty($this->dialogflowProjectId)) {
            Log::error("[" . get_class($this) . "] DIALOGFLOW_PROJECT_ID no se pudo cargar en el constructor.");
            // Puedes lanzar una excepción o manejarlo para que no falle la generación de contextos
            $this->dialogflowProjectId = 'fallback-project-id'; // O alguna forma de manejarlo
        }
    }

    private function loadFlowData(string $normalizedSenderId): void
    { /* ... (similar a InscribirClaseZumbaHandler) ... */
        $this->senderId = $normalizedSenderId;
        $this->cacheKey = self::CACHE_KEY_PREFIX . $this->senderId;
        $cachedData = Cache::get($this->cacheKey);
        $defaultData = ['step' => 'inicio', 'inscripciones_cancelables_mostradas' => [], 'ids_inscripcion_para_confirmar_cancelacion' => []];
        $this->flowData = $cachedData ? array_merge($defaultData, $cachedData) : $defaultData;
    }
    private function saveFlowData(): void
    {
        Cache::put($this->cacheKey, $this->flowData, now()->addMinutes(self::CACHE_TTL_MINUTES));
    }
    private function clearFlowData(): void
    {
        Cache::forget($this->cacheKey);
        $this->flowData = ['step' => 'inicio', 'inscripciones_cancelables_mostradas' => [], 'ids_inscripcion_para_confirmar_cancelacion' => []];
    }

    public function handle(array $parameters, string $normalizedSenderId, ?string $action = null): array
    {
        $this->loadFlowData($normalizedSenderId);
        Log::info("[CancelarInscripcionZumbaHandler {$this->cacheKey}] Action: {$action}, Step: {$this->flowData['step']}");
        Carbon::setLocale('es');
        $cliente = $this->clienteService->findClienteByTelefono($this->senderId);

        if (!$cliente) {
            return $this->prepararRespuesta("No pude encontrarte en el sistema.", $this->generarNombresContextos([], true));
        }

        // Iniciar flujo o si el paso es inicio
        if ($action === 'zumba.cancelacion_inscripcion.iniciar' || ($this->flowData['step'] === 'inicio' && $action !== 'zumba.cancelacion_inscripcion.confirmarSi' && $action !== 'zumba.cancelacion_inscripcion.confirmarNo')) {
            $this->clearFlowData();
            $hoy = Carbon::today();

            $inscripcionesActivas = InscripcionClase::where('inscripciones_clase.cliente_id', $cliente->cliente_id)
                ->where('inscripciones_clase.estado', 'Activa')
                ->where('inscripciones_clase.fecha_clase', '>=', $hoy->toDateString())
                ->join('clases_zumba', 'inscripciones_clase.clase_id', '=', 'clases_zumba.clase_id')
                ->select('inscripciones_clase.*')
                ->with('claseZumba.instructor')
                ->orderBy('inscripciones_clase.fecha_clase', 'asc')
                ->orderBy('clases_zumba.hora_inicio', 'asc')
                ->get();


            $cancelablesText = [];
            $this->flowData['inscripciones_cancelables_mostradas'] = [];

            foreach ($inscripcionesActivas as $insc) {
                $clase = $insc->claseZumba;
                if (!$clase)
                    continue;
                $fechaHoraClase = Carbon::parse($insc->fecha_clase . ' ' . $clase->hora_inicio->format('H:i:s'));
                $horasHastaClase = Carbon::now()->diffInHours($fechaHoraClase, false);

                if ($fechaHoraClase->isFuture() && $horasHastaClase > self::MIN_HORAS_ANTICIPACION_CANCELAR_CLASE) {
                    $detalle = "*ID Insc. {$insc->inscripcion_id}*: {$clase->diasemama} " .
                        Carbon::parse($insc->fecha_clase)->isoFormat('D MMM') . " a las " .
                        $clase->hora_inicio->format('H:i');
                    $cancelablesText[] = $detalle;
                    $this->flowData['inscripciones_cancelables_mostradas'][(string) $insc->inscripcion_id] = $detalle;
                }
            }

            if (empty($cancelablesText)) {
                $this->flowData['step'] = 'finalizado';
                $this->saveFlowData();
                return $this->prepararRespuesta("Hola {$cliente->nombre}, no tienes inscripciones a clases de Zumba que puedas cancelar en este momento (deben ser futuras y con al menos " . self::MIN_HORAS_ANTICIPACION_CANCELAR_CLASE . "h de antelación).", $this->generarNombresContextos([], true));
            }

            $mensajeClases = "Hola {$cliente->nombre}. Estas son tus inscripciones a Zumba que puedes cancelar:\n" . implode("\n", $cancelablesText);
            $mensajeClases .= "\n\nPor favor, dime el ID o los IDs de las inscripciones que quieres cancelar (ej: '120' o '120, 122').";
            $this->flowData['step'] = 'esperando_seleccion_inscripciones_cancelar';
            $this->saveFlowData();
            return $this->prepararRespuesta($mensajeClases, $this->generarNombresContextos(['zumba_cancelacion_esperando_seleccion']));
        }

        // Usuario selecciona inscripciones para cancelar
        if ($action === 'zumba.cancelacion_inscripcion.seleccionar' && $this->flowData['step'] === 'esperando_seleccion_inscripciones_cancelar') {
            $idsSeleccionadosRaw = $parameters['inscripcion_ids_lista'] ?? $parameters['any'] ?? ($parameters['queryResult']['queryText'] ?? null);
            $idsSeleccionados = [];
            if ($idsSeleccionadosRaw) {
                if (is_array($idsSeleccionadosRaw)) {
                    $idsSeleccionados = $idsSeleccionadosRaw;
                } elseif (is_string($idsSeleccionadosRaw) && preg_match_all('/\d+/', $idsSeleccionadosRaw, $matches)) {
                    $idsSeleccionados = $matches[0];
                }
            }

            if (empty($idsSeleccionados)) {
                return $this->prepararRespuesta("No entendí los IDs. Por favor, dime los números de las inscripciones a cancelar.", $this->generarNombresContextos(['zumba_cancelacion_esperando_seleccion']));
            }

            $this->flowData['ids_inscripcion_para_confirmar_cancelacion'] = [];
            $detallesParaConfirmar = "Confirmación de cancelación:\n";
            $alMenosUnaValida = false;

            foreach ($idsSeleccionados as $idStr) {
                $id = trim($idStr);
                if (isset($this->flowData['inscripciones_cancelables_mostradas'][$id])) {
                    $this->flowData['ids_inscripcion_para_confirmar_cancelacion'][] = (int) $id;
                    $detallesParaConfirmar .= "- " . $this->flowData['inscripciones_cancelables_mostradas'][$id] . "\n";
                    $alMenosUnaValida = true;
                } else {
                    $detallesParaConfirmar .= "- ID de inscripción '{$idStr}' no es válido o no se puede cancelar desde aquí.\n";
                }
            }

            if (!$alMenosUnaValida) {
                $this->flowData['step'] = 'esperando_seleccion_inscripciones_cancelar'; // Volver a pedir
                return $this->prepararRespuesta("Ninguno de los IDs proporcionados es válido para cancelar. Revisa la lista e intenta de nuevo.", $this->generarNombresContextos(['zumba_cancelacion_esperando_seleccion']));
            }

            $detallesParaConfirmar .= "\n¿Estás seguro de que quieres cancelar esta(s) inscripción(es)?";
            $this->flowData['step'] = 'esperando_confirmacion_final_cancelar';
            $payload = [
                'buttons' => [
                    ['id' => 'confirmar_si_cancelacion', 'title' => 'Sí, cancelar'],
                    ['id' => 'confirmar_no_cancelacion', 'title' => 'No, mantenerlas']
                ]
            ];
            $this->saveFlowData();
            return $this->prepararRespuesta($detallesParaConfirmar, $this->generarNombresContextos(['zumba_cancelacion_esperando_confirmacion_final']), 'interactive_buttons', $payload);
        }

        // Usuario confirma la cancelación
        if ($action === 'zumba.cancelacion_inscripcion.confirmarSi' && $this->flowData['step'] === 'esperando_confirmacion_final_cancelar') {
            if (empty($this->flowData['ids_inscripcion_para_confirmar_cancelacion'])) { /* ... error ... */
            }
            $respuestasCancelacion = [];
            foreach ($this->flowData['ids_inscripcion_para_confirmar_cancelacion'] as $inscripcionId) {
                $resultado = $this->zumbaService->cancelarInscripcionCliente($cliente->cliente_id, $inscripcionId, self::MIN_HORAS_ANTICIPACION_CANCELAR_CLASE);
                $detalleOriginal = $this->flowData['inscripciones_cancelables_mostradas'][(string) $inscripcionId] ?? "ID {$inscripcionId}";
                $respuestasCancelacion[] = "{$detalleOriginal}: " . $resultado['message'];
            }
            $this->clearFlowData();
            $this->flowData['step'] = 'finalizado';
            return $this->prepararRespuesta(implode("\n", $respuestasCancelacion), $this->generarNombresContextos([], true));
        }

        // Usuario NO confirma la cancelación
        if ($action === 'zumba.cancelacion_inscripcion.confirmarNo' && $this->flowData['step'] === 'esperando_confirmacion_final_cancelar') {
            $this->clearFlowData();
            $this->flowData['step'] = 'finalizado';
            return $this->prepararRespuesta("Entendido. Tus inscripciones no han sido canceladas. ¿Algo más?", $this->generarNombresContextos([], true));
        }

        Log::warning("[CancelarInscripcionZumbaHandler {$this->cacheKey}] Fallback. Action: {$action}, Step: {$this->flowData['step']}");
        $this->clearFlowData();
        return $this->prepararRespuesta("Hubo un problema con la cancelación de clase. ¿Intentamos de nuevo?", $this->generarNombresContextos([], true));
    }

    // Funciones auxiliares
    private function prepararRespuesta(string $fulfillmentText, array $outputContextsToSetActive = [], string $messageType = 'text', array $payload = []): array
    {
        return [
            'messages_to_send' => [
                [
                    'fulfillmentText' => $fulfillmentText,
                    'message_type' => $messageType,
                    'payload' => $payload
                ]
            ],
            'outputContextsToSetActive' => $outputContextsToSetActive
        ];
    }
    private function generarNombresContextos(array $specificContextNamesAsLifespanArray, string $flowContext = 'cancelar_zumba_en_progreso'): array
    {
        $projectId = $this->dialogflowProjectId;
        $sessionId = 'whatsapp-' . $this->senderId;
        $contexts = [];
        if (!$projectId) {
            Log::error("[CancelarInscripcionZumbaHandler] Project ID vacío en generarNombresContextos.");
            return [];
        }

        if ($flowContext && $this->flowData['step'] !== 'inicio' && $this->flowData['step'] !== 'finalizado') {
            $contexts[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/{$flowContext}", 'lifespanCount' => 3];
        }
        foreach ($specificContextNamesAsLifespanArray as $name => $lifespan) {
            if (is_int($name)) {
                $name = $lifespan;
                $lifespan = 2;
            }
            $contexts[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/" . trim($name, '/'), 'lifespanCount' => $lifespan];
        }
        return $contexts;
    }
    private function generarNombresContextosParaLimpiar(array $contextNamesToClear, string $flowContext = 'cancelar_zumba_en_progreso'): array
    {
        $projectId = trim(config('dialogflow.project_id'), '/');
        $sessionId = 'whatsapp-' . $this->senderId;
        $contexts = [];
        if (!$projectId) {
            Log::error("[CancelarInscripcionZumbaHandler] Project ID vacío en generarNombresContextosParaLimpiar.");
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