<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ReservaService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ConsultaDisponibilidadCanchaHandler implements IntentHandlerInterface
{
    private const HORA_INICIO_OPERACION = 8; // Asegúrate que coincida con el Orquestador
    private const HORA_FIN_OPERACION = 22;   // Asegúrate que coincida con el Orquestador
    private const TOTAL_CANCHAS = 3;       // Número total de canchas
    protected ReservaService $reservaService;
    private const CACHE_TTL_MINUTES = 30;

    public function __construct(ReservaService $reservaService)
    {
        $this->reservaService = $reservaService;
    }

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        if (strpos($phoneNumber, 'whatsapp:+') === 0) {
            return substr($phoneNumber, strlen('whatsapp:'));
        }
        return preg_replace('/[^0-9+]/', '', $phoneNumber);
    }

    public function handle(array $parameters, string $senderId, ?string $action = null): array // Cambiado para devolver array
    {
        // El senderId ya debería venir normalizado desde whatsappController
        Log::info('[ConsultaDisponibilidadHandler] Ejecutando. Sender: ' . $senderId . '. Params: ', $parameters);

        $fechaParam = $parameters['fecha'] ?? null;
        if (!$fechaParam) {
            // Este handler ahora es llamado por el Orquestador, que siempre debería proveer la fecha.
            // Si se llama directamente sin fecha, devolvemos un error.
            return [
                'fulfillmentText' => "Por favor, indica para qué fecha quieres consultar la disponibilidad.",
                'message_type' => 'text',
                'payload' => [],
                'outputContextsToSetActive' => []
            ];
        }

        try {
            $fechaConsulta = Carbon::parse($fechaParam)->startOfDay();
            if ($fechaConsulta->isPast() && !$fechaConsulta->isToday()) {
                return ['fulfillmentText' => "No puedes consultar disponibilidad para fechas pasadas.", 'message_type' => 'text'];
            }
        } catch (\Exception $e) {
            Log::error("[ConsultaDisponibilidadHandler] Fecha inválida: {$fechaParam}. Error: " . $e->getMessage());
            return ['fulfillmentText' => "No entendí la fecha que proporcionaste. Inténtalo de nuevo (ej. 'mañana', 'próximo martes').", 'message_type' => 'text'];
        }

        $reservasDelDia = $this->reservaService->getReservasConfirmadasPorFecha($fechaConsulta->toDateString());
        if ($reservasDelDia === null) {
            return ['fulfillmentText' => "Hubo un problema interno al consultar la disponibilidad. Intenta más tarde.", 'message_type' => 'text'];
        }

        $ocupacionPorHora = [];
        for ($h = self::HORA_INICIO_OPERACION; $h < self::HORA_FIN_OPERACION; $h++) {
            $ocupacionPorHora[$h] = 0;
        }

        if (is_array($reservasDelDia) && !empty($reservasDelDia)) {
            foreach ($reservasDelDia as $reserva) {
                try {
                    $inicioReserva = Carbon::parse($reserva['hora_inicio']);
                    $finReserva = Carbon::parse($reserva['hora_fin']);
                    $horaActual = $inicioReserva->copy();
                    while ($horaActual->lt($finReserva)) {
                        $horaKey = $horaActual->hour;
                        if (isset($ocupacionPorHora[$horaKey])) {
                            $ocupacionPorHora[$horaKey]++;
                        }
                        $horaActual->addHour();
                    }
                } catch (\Exception $parseError) {
                    Log::error("[ConsultaDisponibilidadHandler] Error parseando hora de reserva: " . $parseError->getMessage(), ['reserva' => $reserva]);
                }
            }
        }

        $horasDisponibles = [];
        for ($h = self::HORA_INICIO_OPERACION; $h < self::HORA_FIN_OPERACION; $h++) {
            if ($fechaConsulta->isToday() && $h < Carbon::now()->hour) {
                continue; // No mostrar horas pasadas para hoy
            }
            if (isset($ocupacionPorHora[$h]) && $ocupacionPorHora[$h] < self::TOTAL_CANCHAS) {
                $horasDisponibles[] = sprintf('%02d:00', $h);
            }
        }

        $fulfillmentText = "";
        if (empty($horasDisponibles)) {
            $fulfillmentText = "Lo siento, no quedan horas disponibles para el " . $fechaConsulta->locale('es')->isoFormat('dddd D [de] MMMM') . ".";
        } else {
            $fulfillmentText = "Horas con al menos una cancha disponible (inicio de hora):\n" . implode("\n", $horasDisponibles);
        }

        // Devolver el formato de respuesta estándar
        return [
            'fulfillmentText' => $fulfillmentText,
            'message_type' => 'text',
            'payload' => [],
            'outputContextsToSetActive' => [] // Este handler no necesita gestionar contextos de flujo por sí mismo
        ];
    }
}