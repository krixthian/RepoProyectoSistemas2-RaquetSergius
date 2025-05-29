<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ReservaService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ConsultaDisponibilidadCanchaHandler implements IntentHandlerInterface
{
    private const HORA_INICIO_OPERACION = 8;
    private const HORA_FIN_OPERACION = 22;
    private const TOTAL_CANCHAS = 3;
    protected $reservaService;
    private const CACHE_TTL_MINUTES = 30;

    public function __construct(ReservaService $reservaService)
    {
        $this->reservaService = $reservaService;
    }

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        if (strpos($phoneNumber, 'whatsapp:+') === 0) {
            return substr($phoneNumber, strlen('whatsapp:+'));
        }
        return preg_replace('/[^0-9+]/', '', $phoneNumber);
    }

    public function handle(array $parameters, string $senderId, ?string $action = null): string // Devuelve string
    {
        $telefonoNormalizado = $this->normalizePhoneNumber($senderId);
        $reservaCacheKey = 'reserva_cache_' . $telefonoNormalizado;
        $datosReservaEnCache = Cache::get($reservaCacheKey, []);
        $datosReservaEnCache = array_merge([
            'fecha' => null,
            'hora_inicio' => null,
            'paso_actual' => 'inicio',
        ], $datosReservaEnCache);

        Log::info('[ConsultaDisponibilidadHandler] Ejecutando. Sender: ' . $senderId . '. Params: ', $parameters);
        Log::debug('[ConsultaDisponibilidadHandler] Caché inicial: ', $datosReservaEnCache);

        $fechaParam = $parameters['fecha'] ?? null;
        $horaInicioParam = $parameters['horaini'] ?? null; // Dialogflow puede enviar 'horaini'

        $fechaConsulta = null;
        $horaInicioConsultaObj = null;

        if ($fechaParam) {
            try {
                $fechaConsulta = Carbon::parse($fechaParam)->startOfDay();
                $datosReservaEnCache['fecha'] = $fechaConsulta->toDateString();
            } catch (\Exception $e) {
                Log::warning("[ConsultaDisponibilidadHandler] Fecha inválida en parámetro: {$fechaParam}");
                // No hacer nada, se pedirá más adelante si es necesario
            }
        } elseif ($datosReservaEnCache['fecha']) {
            $fechaConsulta = Carbon::parse($datosReservaEnCache['fecha'])->startOfDay();
        }

        if ($horaInicioParam) {
            try {
                $horaInicioConsultaObj = Carbon::parse($horaInicioParam);
                $datosReservaEnCache['hora_inicio'] = $horaInicioConsultaObj->format('H:i:s');
            } catch (\Exception $e) {
                Log::warning("[ConsultaDisponibilidadHandler] Hora inicio inválida en parámetro: {$horaInicioParam}");
            }
        } elseif ($datosReservaEnCache['hora_inicio']) {
            try {
                $horaInicioConsultaObj = Carbon::parse($datosReservaEnCache['hora_inicio']);
            } catch (\Exception $e) {
                // Si la hora en caché es inválida, la limpiamos para que se vuelva a pedir si es necesario.
                $datosReservaEnCache['hora_inicio'] = null;
            }
        }

        // Si después de procesar parámetros y caché, no tenemos fecha, la pedimos.
        if (!$fechaConsulta) {
            $datosReservaEnCache['paso_actual'] = 'esperando_fecha'; // Indicar que el próximo input debe ser una fecha
            Cache::put($reservaCacheKey, $datosReservaEnCache, now()->addMinutes(self::CACHE_TTL_MINUTES));
            Log::debug('[ConsultaDisponibilidadHandler] Solicitando fecha. Caché guardada: ', $datosReservaEnCache);
            return "Por favor, ¿para qué fecha quieres consultar la disponibilidad? (Ej: mañana, próximo lunes)";
        }

        // Si la fecha es pasada (y no es hoy)
        if ($fechaConsulta->isPast() && !$fechaConsulta->isToday()) {
            unset($datosReservaEnCache['fecha']); // Limpiar fecha inválida
            $datosReservaEnCache['paso_actual'] = 'esperando_fecha';
            Cache::put($reservaCacheKey, $datosReservaEnCache, now()->addMinutes(self::CACHE_TTL_MINUTES));
            Log::debug('[ConsultaDisponibilidadHandler] Fecha pasada. Solicitando nueva fecha. Caché guardada: ', $datosReservaEnCache);
            return "Lo siento, no puedes consultar disponibilidad para fechas pasadas. Por favor, indica una fecha a partir de hoy.";
        }

        // Guardar el estado actual en caché (fecha y posiblemente hora_inicio)
        // y el paso siguiente esperado.
        if ($horaInicioConsultaObj) {
            $datosReservaEnCache['paso_actual'] = 'esperando_hora_fin_o_duracion'; // Si ya tenemos fecha y hora_inicio
        } else {
            $datosReservaEnCache['paso_actual'] = 'esperando_hora_inicio'; // Si solo tenemos fecha
        }
        Cache::put($reservaCacheKey, $datosReservaEnCache, now()->addMinutes(self::CACHE_TTL_MINUTES));
        Log::debug('[ConsultaDisponibilidadHandler] Fecha y/o hora procesadas. Caché actualizada: ', $datosReservaEnCache);

        $fechaFormateadaUser = $fechaConsulta->locale('es')->isoFormat('dddd D [de] MMMM');
        $respuesta = "";

        if ($horaInicioConsultaObj) {
            $respuesta .= "Disponibilidad para el {$fechaFormateadaUser} a partir de las " . $horaInicioConsultaObj->format('H:i') . ":\n";
        } else {
            $respuesta .= "Disponibilidad para el {$fechaFormateadaUser}:\n";
        }

        $reservasDelDia = $this->reservaService->getReservasConfirmadasPorFecha($fechaConsulta->toDateString());

        if ($reservasDelDia === null) {
            return "Hubo un problema interno al consultar la disponibilidad. Por favor, intenta de nuevo más tarde.";
        }

        $ocupacionPorHora = [];
        for ($h = self::HORA_INICIO_OPERACION; $h < self::HORA_FIN_OPERACION; $h++) {
            $ocupacionPorHora[$h] = 0;
        }

        if (is_array($reservasDelDia)) {
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
        $filtroHoraInicio = $horaInicioConsultaObj ? $horaInicioConsultaObj->hour : self::HORA_INICIO_OPERACION;

        for ($h = $filtroHoraInicio; $h < self::HORA_FIN_OPERACION; $h++) {
            if ($fechaConsulta->isToday() && $h < Carbon::now()->hour) { // No mostrar horas pasadas para hoy
                continue;
            }
            if (isset($ocupacionPorHora[$h]) && $ocupacionPorHora[$h] < self::TOTAL_CANCHAS) {
                $horasDisponibles[] = sprintf('%02d:00', $h);
            }
        }

        if (empty($horasDisponibles)) {
            $respuesta .= "Lo siento, no quedan horas disponibles ";
            if ($horaInicioConsultaObj) {
                $respuesta .= "a partir de las " . $horaInicioConsultaObj->format('H:i') . ".";
            } else {
                $respuesta .= "para esta fecha.";
            }
            $respuesta .= "\n¿Te gustaría consultar otra fecha u hora?";
        } else {
            $respuesta .= "Horas con al menos una cancha disponible (inicio de hora):\n";
            $respuesta .= implode("\n", $horasDisponibles);
            $respuesta .= "\n\nSi deseas reservar, dime la hora que te interesa?";
        }
        return $respuesta;
    }
}