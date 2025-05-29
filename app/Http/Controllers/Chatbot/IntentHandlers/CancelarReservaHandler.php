<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ReservaService;
use App\Services\ClienteService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache; // AÑADIR CACHE
use Carbon\Carbon;

class CancelarReservaHandler implements IntentHandlerInterface
{
    protected ReservaService $reservaService;
    protected ClienteService $clienteService;

    private const MIN_HORAS_CANCELACION = 2;
    private const CACHE_KEY_PREFIX = 'cancelar_reserva_flow_';
    private const CACHE_TTL_MINUTES = 15; // Tiempo de vida para este flujo corto

    // Datos para la caché de este flujo
    private array $flowData = [
        'step' => 'inicio', // inicio, esperando_confirmacion
        'reserva_id_a_cancelar' => null,
        'detalle_reserva_para_confirmacion' => null,
    ];
    private string $senderId;
    private string $cacheKey;

    public function __construct(ReservaService $reservaService, ClienteService $clienteService)
    {
        $this->reservaService = $reservaService;
        $this->clienteService = $clienteService;
    }

    private function loadFlowData(string $senderId): void
    {
        // Asumimos que senderId ya viene normalizado del whatsappController
        $this->senderId = $senderId;
        $this->cacheKey = self::CACHE_KEY_PREFIX . $this->senderId;
        $cachedData = Cache::get($this->cacheKey);
        $this->flowData = $cachedData ? array_merge($this->flowData, $cachedData) : $this->flowData;
    }

    private function saveFlowData(): void
    {
        Cache::put($this->cacheKey, $this->flowData, now()->addMinutes(self::CACHE_TTL_MINUTES));
    }

    private function clearFlowData(): void
    {
        Cache::forget($this->cacheKey);
        $this->flowData = [
            'step' => 'inicio',
            'reserva_id_a_cancelar' => null,
            'detalle_reserva_para_confirmacion' => null,
        ];
    }

    private function prepararRespuesta(string $fulfillmentText, array $outputContextsToSetActive = [], string $messageType = 'text', array $payload = []): array
    {
        return [
            'fulfillmentText' => $fulfillmentText,
            'message_type' => $messageType,
            'payload' => $payload,
            'outputContextsToSetActive' => $outputContextsToSetActive
        ];
    }

    private function generarNombresContextosActivos(array $specificContextNames, string $flowContext = 'cancelar_reserva_en_progreso'): array
    {
        $projectId = trim(config('dialogflow.project_id'), '/');
        $sessionId = 'whatsapp-' . $this->senderId; // Consistente con whatsappController
        $contexts = [];

        if ($this->flowData['step'] !== 'inicio' && $this->flowData['step'] !== 'finalizado') {
            $contexts[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/{$flowContext}", 'lifespanCount' => 5];
        }
        foreach ($specificContextNames as $name) {
            $cleanName = trim($name, '/');
            $contexts[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/{$cleanName}", 'lifespanCount' => 2];
        }
        return $contexts;
    }
    private function generarNombresContextosParaLimpiar(array $contextNamesToClear, string $flowContext = 'cancelar_reserva_en_progreso'): array
    {
        $projectId = trim(config('dialogflow.project_id'), '/');
        $sessionId = 'whatsapp-' . $this->senderId;
        $contexts = [];
        if ($flowContext) {
            $contexts[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/{$flowContext}", 'lifespanCount' => 0];
        }
        foreach ($contextNamesToClear as $name) {
            $cleanName = trim($name, '/');
            $contexts[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/{$cleanName}", 'lifespanCount' => 0];
        }
        return $contexts;
    }


    public function handle(array $parameters, string $senderId, ?string $action = null): array // Cambiado para devolver array
    {
        $this->loadFlowData($senderId);
        Log::info("[CancelarReservaHandler {$this->cacheKey}] Action: {$action}, Step: {$this->flowData['step']}");
        Carbon::setLocale('es');

        // Acción: Iniciar el proceso de cancelación
        if ($action === 'cancelar.reserva.iniciar' || $this->flowData['step'] === 'inicio') {
            $this->clearFlowData(); // Limpiar cualquier estado anterior de este flujo
            $this->flowData['step'] = 'buscando_reserva';

            $cliente = $this->clienteService->findClienteByTelefono($this->senderId);
            if (!$cliente) {
                Log::warning("[CancelarReservaHandler {$this->cacheKey}] Cliente no encontrado.");
                $this->flowData['step'] = 'finalizado';
                $this->saveFlowData();
                return $this->prepararRespuesta("No pude encontrarte en nuestro sistema. Si tienes una reserva, por favor contacta a recepción.");
            }
            $clienteId = $cliente->cliente_id;
            Log::info("[CancelarReservaHandler {$this->cacheKey}] Cliente encontrado: {$clienteId} ({$cliente->nombre})");

            // Asumimos que findUnicaReservaFutura devuelve una sola o null (o lanza excepción/devuelve colección si hay muchas)
            // Y que ReservaService::clienteTieneReservaFutura existe y es preciso.
            $reservasFuturas = $this->reservaService->getReservasActivasFuturasPorCliente($clienteId);

            if ($reservasFuturas->isEmpty()) {
                Log::info("[CancelarReservaHandler {$this->cacheKey}] No hay reservas futuras para cliente {$clienteId}.");
                $this->flowData['step'] = 'finalizado';
                $this->saveFlowData();
                return $this->prepararRespuesta("Hola {$cliente->nombre}, no encontré reservas futuras activas a tu nombre para cancelar.");
            }

            if ($reservasFuturas->count() > 1) {
                Log::info("[CancelarReservaHandler {$this->cacheKey}] Múltiples reservas futuras para cliente {$clienteId}.");
                $this->flowData['step'] = 'finalizado';
                // Aquí podrías listar las reservas si decides implementar la selección.
                // Por ahora, seguimos tu indicación de simplificar.
                $mensaje = "Hola {$cliente->nombre}, parece que tienes varias reservas activas:\n";
                foreach ($reservasFuturas as $idx => $res) {
                    $fechaRes = Carbon::parse($res->fecha)->locale('es')->isoFormat('D MMM');
                    $horaInicioRes = Carbon::parse($res->hora_inicio)->format('H:i');
                    $cancha = $res->cancha->nombre ?? 'N/A';
                    $mensaje .= ($idx + 1) . ". {$cancha} el {$fechaRes} a las {$horaInicioRes}.\n";
                }
                $mensaje .= "Para cancelar una específica, por favor contacta directamente a recepción.";
                $this->saveFlowData();
                return $this->prepararRespuesta($mensaje);
            }

            // Si llegamos aquí, hay exactamente UNA reserva futura
            $reservaParaCancelar = $reservasFuturas->first();
            $this->flowData['reserva_id_a_cancelar'] = $reservaParaCancelar->reserva_id;
            $fechaReserva = Carbon::parse($reservaParaCancelar->fecha)->locale('es')->isoFormat('dddd D [de] MMMM');
            $horaInicioReserva = Carbon::parse($reservaParaCancelar->hora_inicio)->format('H:i');
            $canchaNombre = $reservaParaCancelar->cancha->nombre ?? 'Cancha';
            $this->flowData['detalle_reserva_para_confirmacion'] = "{$canchaNombre} el {$fechaReserva} a las {$horaInicioReserva}";

            $this->flowData['step'] = 'esperando_confirmacion';
            $mensajeConfirmacion = "Hola {$cliente->nombre}. Encontré tu reserva para {$this->flowData['detalle_reserva_para_confirmacion']}. ¿Estás seguro de que quieres cancelarla?";
            $payload = [
                'buttons' => [
                    ['id' => 'si_confirmar_cancelacion_reserva', 'title' => 'Sí, cancelar'], // Mapear a intent con action cancelar.reserva.confirmarSi
                    ['id' => 'no_mantener_reserva', 'title' => 'No, mantenerla']      // Mapear a intent con action cancelar.reserva.confirmarNo
                ]
            ];
            $contextos = $this->generarNombresContextosActivos(['cancelar_reserva_esperando_confirmacion']);
            $this->saveFlowData();
            return [
                'messages_to_send' => [
                    [
                        'fulfillmentText' => $mensajeConfirmacion,
                        'message_type' => 'interactive_buttons',
                        'payload' => ['buttons' => $payload['buttons']]
                    ]
                ],
                'outputContextsToSetActive' => $contextos
            ];
        }

        // Acción: Usuario confirma que SÍ quiere cancelar
        elseif ($action === 'cancelar.reserva.confirmarSi' && $this->flowData['step'] === 'esperando_confirmacion') {
            if (!$this->flowData['reserva_id_a_cancelar']) {
                Log::error("[CancelarReservaHandler {$this->cacheKey}] No hay reserva_id_a_cancelar en caché para confirmar cancelación.");
                $this->clearFlowData();
                $this->flowData['step'] = 'finalizado';
                return $this->prepararRespuesta("Hubo un problema, no recuerdo qué reserva estábamos cancelando. Por favor, intenta de nuevo.", $this->generarNombresContextosParaLimpiar(['cancelar_reserva_esperando_confirmacion']));
            }

            // Cargar la reserva de nuevo para asegurar que no haya sido modificada/cancelada mientras tanto (opcional pero más seguro)
            $reserva = $this->reservaService->findReservaById($this->flowData['reserva_id_a_cancelar']);
            if (!$reserva || $reserva->estado === 'Cancelada') {
                Log::info("[CancelarReservaHandler {$this->cacheKey}] Reserva ID {$this->flowData['reserva_id_a_cancelar']} ya no es válida o ya está cancelada.");
                $this->clearFlowData();
                $this->flowData['step'] = 'finalizado';
                return $this->prepararRespuesta("Esa reserva ya ha sido cancelada o no se encuentra.", $this->generarNombresContextosParaLimpiar(['cancelar_reserva_esperando_confirmacion']));
            }


            $cancelacionExitosa = $this->reservaService->cancelarReserva($reserva, self::MIN_HORAS_CANCELACION); // Pasar el objeto reserva
            $detalleReserva = $this->flowData['detalle_reserva_para_confirmacion'];
            $this->clearFlowData();
            $this->flowData['step'] = 'finalizado';

            if ($cancelacionExitosa) {
                Log::info("[CancelarReservaHandler {$this->cacheKey}] Reserva ID {$reserva->reserva_id} cancelada exitosamente.");
                return $this->prepararRespuesta("¡Listo! Tu reserva para {$detalleReserva} ha sido cancelada.", $this->generarNombresContextosParaLimpiar(['cancelar_reserva_esperando_confirmacion']));
            } else {
                // Lógica de error del servicio (plazo, etc.)
                $fechaHoraInicioReserva = Carbon::parse($reserva->fecha . ' ' . $reserva->hora_inicio);
                $horasRestantes = Carbon::now()->diffInHours($fechaHoraInicioReserva, false);

                if ($horasRestantes >= 0 && $horasRestantes < self::MIN_HORAS_CANCELACION) {
                    return $this->prepararRespuesta("Lo siento, tu reserva para {$detalleReserva} ya no puede ser cancelada automáticamente porque falta muy poco tiempo (menos de " . self::MIN_HORAS_CANCELACION . " horas). Por favor, contacta a recepción.", $this->generarNombresContextosParaLimpiar(['cancelar_reserva_esperando_confirmacion']));
                } elseif ($horasRestantes < 0) {
                    return $this->prepararRespuesta("La reserva para {$detalleReserva} ya ha pasado y no se puede cancelar.", $this->generarNombresContextosParaLimpiar(['cancelar_reserva_esperando_confirmacion']));
                } else {
                    return $this->prepararRespuesta("Lo siento, no se pudo cancelar tu reserva para {$detalleReserva} en este momento. Por favor, contacta a recepción.", $this->generarNombresContextosParaLimpiar(['cancelar_reserva_esperando_confirmacion']));
                }
            }
        }

        // Acción: Usuario confirma que NO quiere cancelar
        elseif ($action === 'cancelar.reserva.confirmarNo' && $this->flowData['step'] === 'esperando_confirmacion') {
            $detalleReserva = $this->flowData['detalle_reserva_para_confirmacion'] ?? "tu reserva";
            $this->clearFlowData();
            $this->flowData['step'] = 'finalizado';
            return $this->prepararRespuesta("Entendido. Tu reserva para {$detalleReserva} no ha sido cancelada. ¿Algo más en lo que pueda ayudarte?", $this->generarNombresContextosParaLimpiar(['cancelar_reserva_esperando_confirmacion']));
        }

        // Fallback si llega una acción inesperada o el step no coincide
        Log::warning("[CancelarReservaHandler {$this->cacheKey}] Estado/Acción no manejado. Action: {$action}, Step: {$this->flowData['step']}. Limpiando flujo.");
        $this->clearFlowData();
        return $this->prepararRespuesta("Parece que hubo un problema con la cancelación. ¿Podemos intentarlo de nuevo o necesitas ayuda con otra cosa?", $this->generarNombresContextosParaLimpiar([]));
    }
}