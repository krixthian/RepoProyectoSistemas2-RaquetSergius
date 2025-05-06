<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ReservaService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;


class ConsultaDisponibilidadCanchaHandler implements IntentHandlerInterface
{

    private const HORA_INICIO_OPERACION = 9;
    private const HORA_FIN_OPERACION = 22;
    private const TOTAL_CANCHAS = 3;

    protected $reservaService;


    public function __construct(ReservaService $reservaService)
    {
        $this->reservaService = $reservaService;
    }

    /**
     * Maneja la consulta de disponibilidad de canchas.
     *
     * @param array $parameters Parámetros de Dialogflow (espera 'fecha').
     * @param string $senderId ID del remitente.
     * @return string Respuesta para el usuario.
     */
    public function handle(array $parameters, string $senderId): string
    {
        Log::info('Executing ConsultaDisponibilidadCanchaHandler');


        $fechaParam = $parameters['fecha'] ?? null;
        $responseText = "Por favor, indica la fecha para la que quieres consultar la disponibilidad (ej. 'mañana', 'el próximo jueves', '15 de abril').";

        // Procesa solo si se recibió el parámetro 'fecha'
        if ($fechaParam) {
            try {
                // Parsea la fecha recibida y la establece al inicio del día
                $fechaConsulta = Carbon::parse($fechaParam)->startOfDay();


                $fechaFormateada = $fechaConsulta->format('d/m/Y');


                // Formato Y-m-d para el servicio/API
                $fechaParaServicio = $fechaConsulta->toDateString();

                if ($fechaConsulta->isPast() && !$fechaConsulta->isToday()) {
                    return "Lo siento, no puedes consultar disponibilidad para fechas pasadas. Por favor, indica una fecha a partir de hoy.";
                }

                Log::info("Calling ReservaService->getReservasConfirmadasPorFecha directly for " . $fechaParaServicio);
                $reservasDelDia = $this->reservaService->getReservasConfirmadasPorFecha($fechaParaServicio);

                if ($reservasDelDia === null) {
                    return "Hubo un problema interno al consultar la disponibilidad. Por favor, intenta de nuevo más tarde.";
                }

                Log::info("ReservaService call successful. Found " . count($reservasDelDia) . " reservations for " . $fechaParaServicio);


                $ocupacionPorHora = [];
                for ($h = self::HORA_INICIO_OPERACION; $h < self::HORA_FIN_OPERACION; $h++) {
                    $ocupacionPorHora[$h] = 0;
                }

                if (is_array($reservasDelDia) && !empty($reservasDelDia)) {
                    foreach ($reservasDelDia as $reserva) {
                        try {
                            $inicioReserva = Carbon::parse($reserva['hora_inicio']);
                            $finReserva = Carbon::parse($reserva['hora_fin']);

                            // Incrementa el contador para cada hora que abarca la reserva
                            $horaActual = $inicioReserva->copy();
                            while ($horaActual->lt($finReserva)) {
                                $horaKey = $horaActual->hour;
                                // Solo cuenta si la hora está dentro del rango de operación
                                if (isset($ocupacionPorHora[$horaKey])) {
                                    $ocupacionPorHora[$horaKey]++;
                                }
                                $horaActual->addHour(); // Pasa a la siguiente hora
                            }
                        } catch (\Exception $parseError) {
                            // Logea si hay error parseando las horas de una reserva específica
                            Log::error("Error parsing reservation time: " . $parseError->getMessage() . " Data: " . json_encode($reserva));
                            // Continúa con la siguiente reserva
                        }
                    }
                }

                $horasDisponibles = [];
                for ($h = self::HORA_INICIO_OPERACION; $h < self::HORA_FIN_OPERACION; $h++) {
                    if (isset($ocupacionPorHora[$h]) && $ocupacionPorHora[$h] < self::TOTAL_CANCHAS) {
                        $horasDisponibles[] = sprintf('%02d:00', $h); // Formato HH:00
                    }
                }

                $horasOcupadas = [];
                for ($h = self::HORA_INICIO_OPERACION; $h < self::HORA_FIN_OPERACION; $h++) {
                    if (!(isset($ocupacionPorHora[$h]) && $ocupacionPorHora[$h] < self::TOTAL_CANCHAS)) {
                        $horasOcupadas[] = sprintf('%02d:00', $h); // Formato HH:00
                    }
                }


                if (empty($horasDisponibles)) {
                    $responseText = "Lo siento, no quedan horas disponibles para el {$fechaFormateada}. ¿Te gustaría consultar otra fecha?";
                } else {
                    $responseText = "Para el {$fechaFormateada}, las horas con al menos una cancha disponible (inicio de hora) son:\n";
                    $responseText .= implode("\n", $horasDisponibles); // Lista las horas disponibles

                    $responseText .= "\n\nLas horas ocupadas son:\n~";
                    $responseText .= implode("\n", $horasOcupadas); // Lista las horas ocupadas
                    $responseText .= "~";

                    $responseText .= "\n\nPor favor, indica la hora que te gustaría reservar (por ejemplo: 'quiero reservar el jueves a las 14:00')."; // Sugiere siguiente paso
                }

                // Captura de Excepciones Específicas
            } catch (\Carbon\Exceptions\InvalidFormatException $e) {
                Log::error("Invalid date format received from Dialogflow: " . json_encode($fechaParam));
                $responseText = "No entendí la fecha que proporcionaste. Por favor, inténtalo de nuevo (ej. 'mañana', 'el próximo martes', '15 de abril').";
            } catch (\Exception $e) {
                // Captura cualquier otra excepción inesperada durante el proceso
                Log::error("Exception in ConsultaDisponibilidadCanchaHandler: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                $responseText = "Ocurrió un error inesperado al consultar la disponibilidad. Intenta de nuevo más tarde.";
            }
        }

        return $responseText;
    }
}