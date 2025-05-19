<?php

namespace App\Services;

use App\Models\Reserva;
use App\Models\Cliente;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use App\Models\Cancha;

class ReservaService
{
    /**
     * Obtiene las reservas confirmadas para una fecha específica.
     * Extraído de ReservaController::getReservasByDate
     *
     * @param string $fechaString // Fecha en formato 'Y-m-d'
     * @return array|null Retorna un array de reservas o null si hay error
     */
    public function getReservasConfirmadasPorFecha(string $fechaString): ?array
    {
        try {
            $fecha = Carbon::parse($fechaString)->format('Y-m-d');
            $reservas = Reserva::whereDate('fecha', $fecha)
                ->where('estado', 'Confirmada')
                ->get(['hora_inicio', 'hora_fin', 'estado', 'cancha_id'])
                ->toArray();
            return $reservas;
        } catch (\Exception $e) {
            Log::error("Error en ReservaService al obtener reservas para fecha {$fechaString}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica si un cliente ya tiene una reserva futura (pendiente o confirmada).
     *
     * @param int $clienteId ID del cliente.
     * @return bool True si tiene una reserva futura, False si no.
     */
    public function clienteTieneReservaFutura(int $clienteId): bool
    {
        try {
            $hoy = Carbon::today()->toDateString();
            $tieneReserva = Reserva::where('cliente_id', $clienteId)
                ->where('fecha', '>=', $hoy) // Desde hoy en adelante
                ->whereIn('estado', ['Pendiente', 'Confirmada']) // Solo las activas
                ->exists(); // Devuelve true si encuentra al menos una

            Log::info("ReservaService: Check future reservation for client {$clienteId}. Result: " . ($tieneReserva ? 'Yes' : 'No'));
            return $tieneReserva;

        } catch (\Exception $e) {
            Log::error("ReservaService: Error checking future reservation for client {$clienteId}: " . $e->getMessage());
            // Considera qué devolver en caso de error. False podría permitir reservar por error.
            // True podría bloquear injustamente. Quizás lanzar una excepción o devolver null y manejarlo en el handler.
            // Por seguridad, devolvemos true para evitar doble reserva si hay error.
            return true;
        }
    }

    /**
     * Crea una reserva para un cliente, asignando la primera cancha disponible.
     *
     * @param int $clienteId
     * @param string $fechaIso Dialogflow date (e.g., "2023-10-27T12:00:00-04:00")
     * @param string $horaInicioIso Dialogflow time (e.g., "2023-10-27T14:00:00-04:00")
     * @param array|null $duracion Dialogflow duration object (e.g., {"amount": 3600, "unit": "s"}) o null si se usa horaFinIso
     * @param string|null $horaFinIso Dialogflow end time (e.g., "2023-10-27T15:00:00-04:00") o null si se usa duracion
     * @param Cliente $cliente Objeto del cliente para datos adicionales (email, nombre)
     * @return array ['success' => bool, 'message' => string, 'data' => Reserva|null]
     */
    public function crearReservaEnPrimeraCanchaLibre(
        int $clienteId,
        string $fechaIso,
        string $horaInicioIso,
        ?array $duracion, // Ejemplo: {"amount": 3600, "unit": "s"} o {"amount": 1, "unit": "h"}
        ?string $horaFinIso,
        Cliente $cliente
    ): array {
        Log::info("[ReservaService] Iniciando creación de reserva en primera cancha libre.", compact('clienteId', 'fechaIso', 'horaInicioIso', 'duracion', 'horaFinIso'));

        try {
            $fechaReserva = Carbon::parse($fechaIso)->startOfDay(); // Solo la fecha
            $horaInicioObj = Carbon::parse($horaInicioIso); // Hora de inicio con su fecha original

            // Combinar la fecha deseada con la hora de inicio deseada
            $fechaHoraInicioReserva = $fechaReserva->copy()->setTimeFrom($horaInicioObj);
            $horaFinCalculada = null;

            if ($horaFinIso) {
                $horaFinObj = Carbon::parse($horaFinIso);
                $horaFinCalculada = $fechaReserva->copy()->setTimeFrom($horaFinObj);
                if ($horaFinCalculada->lessThanOrEqualTo($fechaHoraInicioReserva)) {
                    return ['success' => false, 'message' => 'La hora de fin debe ser posterior a la hora de inicio.'];
                }
            } elseif ($duracion && isset($duracion['amount']) && isset($duracion['unit'])) {
                try {
                    // Convertir unidades de Dialogflow a CarbonInterval
                    // Dialogflow V2 usa 's', 'min', 'h', 'day', 'wk', 'mo', 'yr' para 'unit'
                    // CarbonInterval espera algo como '1 hour', '90 minutes', etc.
                    // O directamente en segundos.
                    $seconds = 0;
                    switch ($duracion['unit']) {
                        case 's':
                            $seconds = $duracion['amount'];
                            break;
                        case 'min':
                            $seconds = $duracion['amount'] * 60;
                            break;
                        case 'h':
                            $seconds = $duracion['amount'] * 3600;
                            break;
                        default:
                            return ['success' => false, 'message' => 'La unidad de duración no es válida. Por favor, usa horas o minutos.'];
                    }
                    if ($seconds <= 0) {
                        return ['success' => false, 'message' => 'La duración debe ser mayor a cero.'];
                    }
                    $horaFinCalculada = $fechaHoraInicioReserva->copy()->addSeconds($seconds);
                } catch (\Exception $e) {
                    Log::error("[ReservaService] Error al parsear duración: " . $e->getMessage(), ['duracion' => $duracion]);
                    return ['success' => false, 'message' => 'No se pudo entender la duración proporcionada.'];
                }
            } else {
                // Duración por defecto si no se especifica (ej. 1 hora)
                $horaFinCalculada = $fechaHoraInicioReserva->copy()->addHour();
                Log::info("[ReservaService] Usando duración por defecto de 1 hora.");
            }

            // Validaciones de tiempo
            if ($fechaReserva->isPast() && !$fechaReserva->isToday()) {
                return ['success' => false, 'message' => 'No puedes realizar reservas para fechas pasadas.'];
            }
            if ($fechaReserva->isToday() && $fechaHoraInicioReserva->isPast()) {
                return ['success' => false, 'message' => 'No puedes realizar reservas para horas pasadas hoy.'];
            }

            $horaMinima = 8;
            $horaMaximaSistema = 22;
            if ($fechaHoraInicioReserva->hour < $horaMinima || $horaFinCalculada->hour > $horaMaximaSistema || ($horaFinCalculada->hour == $horaMaximaSistema && $horaFinCalculada->minute > 0)) {
                return ['success' => false, 'message' => "Solo puedes reservar canchas entre las {$horaMinima}:00 y las {$horaMaximaSistema}:00.\n Si tienes problemas usa el formato de 24 horas ej. '14:00'"];
            }

            // Obtener todas las canchas activas y disponibles
            $canchasActivas = Cancha::where('disponible', true)
                ->orderBy('cancha_id') // O por algún criterio de preferencia
                ->get();

            if ($canchasActivas->isEmpty()) {
                return ['success' => false, 'message' => 'No hay canchas configuradas o habilitadas en el sistema.'];
            }

            $canchaAsignada = null;
            $montoReserva = 0;

            foreach ($canchasActivas as $cancha) {
                $reservaExistente = Reserva::where('cancha_id', $cancha->cancha_id)
                    ->where('fecha', $fechaReserva->toDateString())
                    ->where(function ($query) use ($fechaHoraInicioReserva, $horaFinCalculada) {
                        $query->where(function ($q) use ($fechaHoraInicioReserva, $horaFinCalculada) {
                            $q->where('hora_inicio', '<', $horaFinCalculada->toTimeString())
                                ->where('hora_fin', '>', $fechaHoraInicioReserva->toTimeString());
                        });
                    })
                    ->where('estado', '!=', 'Cancelada')
                    ->exists();

                if (!$reservaExistente) {
                    $canchaAsignada = $cancha;
                    $montoReserva = $canchaAsignada->precio_hora * ($fechaHoraInicioReserva->diffInMinutes($horaFinCalculada) / 60); // Cálculo del monto por duración
                    break;
                }
            }

            if (!$canchaAsignada) {
                Log::info("[ReservaService] No se encontraron canchas disponibles.", ['fecha' => $fechaReserva->toDateString(), 'inicio' => $fechaHoraInicioReserva->toTimeString(), 'fin' => $horaFinCalculada->toTimeString()]);
                return ['success' => false, 'message' => "Lo sentimos, no hay canchas disponibles para el {$fechaReserva->isoFormat('dddd D [de] MMMM')} de {$fechaHoraInicioReserva->format('H:i')} a {$horaFinCalculada->format('H:i')}."];
            }

            Log::info("[ReservaService] Cancha asignada: ID {$canchaAsignada->cancha_id} ({$canchaAsignada->nombre_cancha})");

            DB::beginTransaction();
            $nuevaReserva = Reserva::create([
                'cliente_id' => $clienteId,
                'cancha_id' => $canchaAsignada->cancha_id,
                'fecha' => $fechaReserva->toDateString(),
                'hora_inicio' => $fechaHoraInicioReserva->toTimeString(),
                'hora_fin' => $horaFinCalculada->toTimeString(),
                'monto' => $montoReserva,
                'pago_completo' => false,
                'estado' => 'Confirmada',
                'metodo_pago' => 'Efectivo'
            ]);
            DB::commit();
            Log::info("[ReservaService] Reserva creada exitosamente.", ['reserva_id' => $nuevaReserva->getKey()]); // Usar getKey() para la PK


            return [
                'success' => true,
                'message' => "¡Tu reserva para la cancha '{$canchaAsignada->nombre_cancha}' el {$fechaReserva->isoFormat('dddd D [de] MMMM')} de {$fechaHoraInicioReserva->format('H:i')} a {$horaFinCalculada->format('H:i')} ha sido confirmada! El monto es de " . number_format($montoReserva, 2) . " Bs.",
                'data' => $nuevaReserva
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[ReservaService] Error general al crear reserva en primera cancha libre: " . $e->getMessage(), [
                'clienteId' => $clienteId,
                'fechaIso' => $fechaIso,
                'horaInicioIso' => $horaInicioIso,
                'duracion' => $duracion,
                'horaFinIso' => $horaFinIso,
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Lo siento, ocurrió un error inesperado al procesar tu reserva. Por favor, intenta más tarde.'];
        }
    }


    /**
     * Busca la *única* reserva futura activa (Pendiente/Confirmada) para un cliente.
     *
     * @param int $clienteId
     * @return Reserva|null Retorna el modelo Reserva si encuentra una única, null si no encuentra ninguna, o si encuentra múltiples (para evitar ambigüedad).
     */
    //BUSCAR RESERVAR PROXIMA
    public function findUnicaReservaFutura(int $clienteId): ?Reserva
    {
        Log::info("ReservaService: Finding single future reservation for client {$clienteId}");
        try {
            $hoy = Carbon::today()->toDateString();
            $reservasFuturas = Reserva::where('cliente_id', $clienteId)
                ->where('fecha', '>=', $hoy)
                ->whereIn('estado', ['Pendiente', 'Confirmada'])
                ->orderBy('fecha', 'asc') // Opcional: tomar la más próxima si hay varias?
                ->get();

            // Verifica si se encontró exactamente una reserva
            if ($reservasFuturas->count() === 1) {
                Log::info("ReservaService: Found one future reservation.");
                return $reservasFuturas->first();
            } elseif ($reservasFuturas->count() > 1) {
                Log::warning("ReservaService: Found multiple ({$reservasFuturas->count()}) future reservations for client {$clienteId}. Cannot uniquely identify which one to cancel via bot.");
                // Podrías devolver las reservas para que el handler las liste, o null para indicar ambigüedad.
                // Devolver null es más simple para el flujo actual del bot.
                return null; // Indica ambigüedad o más de una reserva
            } else {
                Log::info("ReservaService: No future reservations found for client {$clienteId}.");
                return null; // No encontró ninguna
            }

        } catch (\Exception $e) {
            Log::error("ReservaService: Error finding single future reservation for client {$clienteId}: " . $e->getMessage());
            return null; // Error durante la búsqueda
        }
    }



    /**
     * Cambia el estado de una reserva a 'Cancelada'.
     * Incluye validación básica de tiempo de cancelación (ej. 2 horas antes).
     *
     * @param Reserva $reserva El objeto Reserva a cancelar.
     * @param int $horasAntelacionMinimas Mínimo de horas antes para poder cancelar.
     * @return bool True si la cancelación fue exitosa, False en caso contrario.
     */
    public function cancelarReserva(Reserva $reserva, int $horasAntelacionMinimas = 2): bool
    {
        Log::info("ReservaService: Attempting to cancel reservation ID: {$reserva->reserva_id}");
        try {
            // Combina fecha y hora_inicio para obtener el momento exacto de inicio
            $fechaHoraInicioReserva = $reserva->fecha->copy()->setTimeFromTimeString($reserva->hora_inicio);
            $ahora = Carbon::now();

            // --- Validación de Política de Cancelación ---
            if ($ahora->gte($fechaHoraInicioReserva)) {
                Log::warning("ReservaService: Attempt to cancel reservation ID {$reserva->reserva_id} that has already started or passed.");
                return false; // Ya pasó o está en curso
            }
            if ($ahora->diffInHours($fechaHoraInicioReserva) < $horasAntelacionMinimas) {
                Log::warning("ReservaService: Attempt to cancel reservation ID {$reserva->reserva_id} within the minimum notice period ({$horasAntelacionMinimas} hours).");
                return false; // Demasiado tarde para cancelar
            }
            // --- Fin Validación ---


            // Verifica si ya está cancelada
            if ($reserva->estado === 'Cancelada') {
                Log::info("ReservaService: Reservation ID {$reserva->reserva_id} is already cancelled.");
                return true; // Ya estaba cancelada, considera éxito
            }

            // Actualiza el estado
            $reserva->estado = 'Cancelada';
            $success = $reserva->save(); // Guarda los cambios en la BD

            if ($success) {
                Log::info("ReservaService: Reservation ID {$reserva->reserva_id} cancelled successfully.");
                // TODO: Aquí podrías disparar un evento, enviar notificación, etc.
            } else {
                Log::error("ReservaService: Failed to save cancellation for reservation ID {$reserva->reserva_id}.");
            }
            return $success;

        } catch (\Exception $e) {
            Log::error("ReservaService: Error cancelling reservation ID {$reserva->reserva_id}: " . $e->getMessage());
            return false;
        }
    }
}