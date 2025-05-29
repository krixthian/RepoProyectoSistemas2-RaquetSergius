<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ClienteService;
use App\Services\ZumbaService; // Necesitarás este servicio
use App\Models\InscripcionClase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CancelarInscripcionZumbaHandler implements IntentHandlerInterface
{
    protected ClienteService $clienteService;
    protected ZumbaService $zumbaService;

    private const CACHE_KEY_PREFIX = 'cancelar_zumba_';
    private const CACHE_TTL_MINUTES = 15;
    private const MIN_HORAS_ANTICIPACION_CANCELAR_CLASE = 2; // Ejemplo

    private array $flowData = [
        'step' => 'inicio', // inicio, esperando_seleccion_clases, esperando_confirmacion_final
        'inscripciones_cancelables_mostradas' => [], // IDs de inscripción
        'ids_inscripcion_para_confirmar_cancelacion' => [],
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
        if ($action === 'zumba.cancelacion.iniciar' || $this->flowData['step'] === 'inicio') {
            $this->clearFlowData();
            $hoy = Carbon::today();
            $inscripcionesActivas = InscripcionClase::where('cliente_id', $cliente->cliente_id)
                ->where('estado', 'Activa')
                ->whereHas('claseZumba', function ($q) use ($hoy) {
                    // Lógica para determinar si la clase es futura
                    // Esto depende de cómo almacenes las fechas de las clases (diasemama vs fecha_especifica)
                    // Aquí simplificaremos asumiendo que se pueden cancelar las de hoy que no han pasado o futuras
                    // Necesitarías una lógica más robusta para convertir diasemama a fechas concretas
                })
                ->with('claseZumba.instructor')
                ->get();

            $cancelables = [];
            $mensajeClases = "Hola {$cliente->nombre}. Tus próximas clases de Zumba a las que estás inscrito y puedes cancelar son:\n";

            foreach ($inscripcionesActivas as $insc) {
                $clase = $insc->claseZumba;
                if (!$clase)
                    continue;

                // Determinar la próxima fecha de esta clase (lógica simplificada)
                // Necesitas una función robusta para esto si usas 'diasemama'
                $proximaFechaClase = null;
                for ($i = 0; $i < 7; $i++) {
                    $fechaIntento = Carbon::today()->addDays($i);
                    if (ucfirst($fechaIntento->dayName) === $clase->diasemama) {
                        $fechaHoraIntento = Carbon::parse($fechaIntento->toDateString() . ' ' . $clase->hora_inicio->format('H:i:s'));
                        if ($fechaHoraIntento->isFuture() && $fechaHoraIntento->diffInHours(Carbon::now(), false) > -self::MIN_HORAS_ANTICIPACION_CANCELAR_CLASE) { //diffInHours con false devuelve negativo si es futuro
                            $proximaFechaClase = $fechaHoraIntento;
                            break;
                        }
                    }
                }

                if ($proximaFechaClase) {
                    $detalle = "ID Insc. {$insc->inscripcion_id}: {$clase->diasemama} " . $proximaFechaClase->isoFormat('D MMM H:mm');
                    $cancelables[$insc->inscripcion_id] = $detalle; // Usar ID de inscripción
                    $this->flowData['inscripciones_cancelables_mostradas'][$insc->inscripcion_id] = $detalle;
                }
            }

            if (empty($cancelables)) {
                $this->flowData['step'] = 'finalizado';
                $this->saveFlowData();
                return $this->prepararRespuesta("No tienes inscripciones a clases de Zumba que puedas cancelar en este momento (deben ser con al menos " . self::MIN_HORAS_ANTICIPACION_CANCELAR_CLASE . "h de antelación).", $this->generarNombresContextos([], true));
            }

            foreach ($cancelables as $id => $detalle) {
                $mensajeClases .= "- {$detalle}\n";
            }
            $mensajeClases .= "\nPor favor, dime el ID o los IDs de las inscripciones que quieres cancelar (ej: '1' o '1, 2, 3').";
            $this->flowData['step'] = 'esperando_seleccion_clases_cancelar';
            $this->saveFlowData();
            return $this->prepararRespuesta($mensajeClases, $this->generarNombresContextos(['zumba_cancelacion_esperando_seleccion']));
        }

        // Usuario selecciona clases para cancelar
        if ($action === 'zumba.cancelacion.seleccionarClases' && $this->flowData['step'] === 'esperando_seleccion_clases_cancelar') {
            $idsSeleccionadosRaw = $parameters['inscripcion_ids'] ?? $parameters['any'] ?? $currentDialogflowParams['queryResult']['queryText'] ?? '';
            $idsSeleccionados = [];
            if (preg_match_all('/\d+/', $idsSeleccionadosRaw, $matches)) {
                $idsSeleccionados = $matches[0];
            }

            if (empty($idsSeleccionados)) {
                return $this->prepararRespuesta("No entendí los IDs. Por favor, dime los números de las inscripciones a cancelar.", $this->generarNombresContextos(['zumba_cancelacion_esperando_seleccion']));
            }

            $this->flowData['ids_inscripcion_para_confirmar_cancelacion'] = [];
            $detallesParaConfirmar = "Estás a punto de cancelar la(s) siguiente(s) inscripción(es):\n";
            $alMenosUnaValida = false;

            foreach ($idsSeleccionados as $idStr) {
                $id = (int) trim($idStr);
                if (isset($this->flowData['inscripciones_cancelables_mostradas'][$id])) {
                    $this->flowData['ids_inscripcion_para_confirmar_cancelacion'][] = $id;
                    $detallesParaConfirmar .= "- " . $this->flowData['inscripciones_cancelables_mostradas'][$id] . "\n";
                    $alMenosUnaValida = true;
                } else {
                    $detallesParaConfirmar .= "- ID de inscripción '{$idStr}' no es válido o no se puede cancelar.\n";
                }
            }

            if (!$alMenosUnaValida) {
                $this->flowData['step'] = 'esperando_seleccion_clases_cancelar'; // Volver a pedir
                $this->saveFlowData();
                return $this->prepararRespuesta("Ninguno de los IDs proporcionados es válido para cancelar. Por favor, revisa la lista e inténtalo de nuevo.", $this->generarNombresContextos(['zumba_cancelacion_esperando_seleccion']));
            }

            $detallesParaConfirmar .= "\n¿Estás seguro?";
            $this->flowData['step'] = 'esperando_confirmacion_final_cancelacion';
            $payload = [
                'buttons' => [
                    ['id' => 'si_confirmar_cancelacion_zumba', 'title' => 'Sí, cancelar'],
                    ['id' => 'no_cancelar_inscripciones_zumba', 'title' => 'No, mantenerlas']
                ]
            ];
            $this->saveFlowData();
            return $this->prepararRespuesta($detallesParaConfirmar, $this->generarNombresContextos(['zumba_cancelacion_esperando_confirmacion_final']), 'interactive_buttons', $payload);
        }

        // Usuario confirma la cancelación
        if ($action === 'zumba.cancelacion.confirmarSi' && $this->flowData['step'] === 'esperando_confirmacion_final_cancelacion') {
            if (empty($this->flowData['ids_inscripcion_para_confirmar_cancelacion'])) {
                $this->clearFlowData();
                $this->flowData['step'] = 'finalizado';
                return $this->prepararRespuesta("Hubo un problema, no recuerdo qué inscripciones estábamos cancelando.", $this->generarNombresContextos([], true));
            }
            $respuestasCancelacion = [];
            foreach ($this->flowData['ids_inscripcion_para_confirmar_cancelacion'] as $inscripcionId) {
                $resultado = $this->zumbaService->cancelarInscripcionCliente($cliente->cliente_id, $inscripcionId, self::MIN_HORAS_ANTICIPACION_CANCELAR_CLASE);
                $respuestasCancelacion[] = $resultado['message'];
            }
            $this->clearFlowData();
            $this->flowData['step'] = 'finalizado';
            return $this->prepararRespuesta(implode("\n", $respuestasCancelacion), $this->generarNombresContextos([], true));
        }

        // Usuario NO confirma la cancelación
        if ($action === 'zumba.cancelacion.confirmarNo' && $this->flowData['step'] === 'esperando_confirmacion_final_cancelacion') {
            $this->clearFlowData();
            $this->flowData['step'] = 'finalizado';
            return $this->prepararRespuesta("Entendido. Tus inscripciones no han sido canceladas. ¿Algo más?", $this->generarNombresContextos([], true));
        }

        Log::warning("[CancelarInscripcionZumbaHandler {$this->cacheKey}] Fallback. Action: {$action}, Step: {$this->flowData['step']}");
        $this->clearFlowData();
        return $this->prepararRespuesta("Parece que hubo un problema con la cancelación de clase. ¿Intentamos de nuevo?", $this->generarNombresContextos([], true));
    }

    private function prepararRespuesta(string $fulfillmentText, array $outputContextsToSetActive = [], string $messageType = 'text', array $payload = []): array
    {
        return [
            'messages_to_send' => [['fulfillmentText' => $fulfillmentText, 'message_type' => $messageType, 'payload' => $payload]],
            'outputContextsToSetActive' => $outputContextsToSetActive
        ];
    }
    private function generarNombresContextos(array $specificContextNames, string $flowContext = 'zumba_inscripcion_en_progreso'): array
    {
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
        foreach ($specificContextNames as $nameInfo) {
            $name = is_array($nameInfo) ? $nameInfo['name'] : $nameInfo;
            $lifespan = is_array($nameInfo) && isset($nameInfo['lifespan']) ? $nameInfo['lifespan'] : 2;
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