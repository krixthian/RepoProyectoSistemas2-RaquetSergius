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
     *
     * @param string $fechaString 
     * @return array|null 
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
     * Verifica si un cliente ya tiene una reserva futura
     * @param int $clienteId 
     * @return bool 
     */
    public function clienteTieneReservaFutura(int $clienteId): bool
    {
        try {
            $hoy = Carbon::today()->toDateString();
            $tieneReserva = Reserva::where('cliente_id', $clienteId)
                ->where('fecha', '>=', $hoy)
                ->whereIn('estado', ['Pendiente', 'Confirmada'])
                ->exists();

            Log::info("ReservaService: Check future reservation for client {$clienteId}. Result: " . ($tieneReserva ? 'Yes' : 'No'));
            return $tieneReserva;

        } catch (\Exception $e) {
            Log::error("ReservaService: Error checking future reservation for client {$clienteId}: " . $e->getMessage());

            return true;
        }
    }

    /**
     * Crea una reserva para un cliente, asignando la primera cancha disponible.
     *
     * @param int $clienteId
     * @param string $fechaIso
     * @param string $horaInicioIso 
     * @param array|null $duracion 
     * @param string|null $horaFinIso 
     * @param Cliente $cliente 
     * @return array 
     */
    public function crearReservaEnPrimeraCanchaLibre(
        int $clienteId,
        string $fechaIso,
        string $horaInicioIso,
        ?array $duracion,
        ?string $horaFinIso,
        Cliente $cliente
    ): array {
        Log::info("[ReservaService] Iniciando creación de reserva en primera cancha libre.", compact('clienteId', 'fechaIso', 'horaInicioIso', 'duracion', 'horaFinIso'));

        try {
            $fechaReserva = Carbon::parse($fechaIso)->startOfDay();
            $horaInicioObj = Carbon::parse($horaInicioIso);

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
                        case 'hora':
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
                $horaFinCalculada = $fechaHoraInicioReserva->copy()->addHour();
                Log::info("[ReservaService] Usando duración por defecto de 1 hora.");
            }

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

            $canchasActivas = Cancha::where('disponible', true)
                ->orderBy('cancha_id')
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

            //Actualizacion last_activity_at del cliente (CHURN)
            if ($cliente) {
                $cliente->last_activity_at = Carbon::now();
                $cliente->is_churned = false; // Una nueva actividad significa que ya no está en churn
                $cliente->save();
                Log::info("[ReservaService] Actualizado last_activity_at para cliente ID {$cliente->cliente_id} por reserva.");
            } else {
                Log::warning("[ReservaService] No se pudo actualizar last_activity_at: objeto cliente no proporcionado.");
            }


            DB::commit();
            Log::info("[ReservaService] Reserva creada exitosamente.", ['reserva_id' => $nuevaReserva->getKey()]); // Usar getKey() para la PK


            return [
                'success' => true,
                'message' => "¡Tu reserva de cancha #{$canchaAsignada->cancha_id} de wally  el {$fechaReserva->locale('es')->isoFormat('dddd D [de] MMMM')} de {$fechaHoraInicioReserva->format('H:i')} a {$horaFinCalculada->format('H:i')} ha sido confirmada! El monto es de " . number_format($montoReserva, 2) . " Bs., Te esperamos!",
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
     *
     * @param int $clienteId
     * @return Reserva|null    
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
                ->orderBy('fecha', 'asc')
                ->get();


            if ($reservasFuturas->count() === 1) {
                Log::info("ReservaService: Found one future reservation.");
                return $reservasFuturas->first();
            } elseif ($reservasFuturas->count() > 1) {
                Log::warning("ReservaService: Found multiple ({$reservasFuturas->count()}) future reservations for client {$clienteId}. Cannot uniquely identify which one to cancel via bot.");

                return null;
            } else {
                Log::info("ReservaService: No future reservations found for client {$clienteId}.");
                return null;
            }

        } catch (\Exception $e) {
            Log::error("ReservaService: Error finding single future reservation for client {$clienteId}: " . $e->getMessage());
            return null;
        }
    }

    public function getReservasActivasFuturasPorCliente(int $clienteId)
    {
        $hoy = Carbon::today()->toDateString();
        $ahora = Carbon::now()->format('H:i:s');

        return Reserva::where('cliente_id', $clienteId)
            ->where('estado', 'Confirmada') // O el estado que uses para reservas válidas
            ->where(function ($query) use ($hoy, $ahora) {
                $query->where('fecha', '>', $hoy)
                    ->orWhere(function ($query) use ($hoy, $ahora) {
                        $query->where('fecha', '=', $hoy)
                            ->where('hora_fin', '>', $ahora);
                    });
            })
            ->with('cancha') // Para mostrar el nombre de la cancha
            ->orderBy('fecha', 'asc')
            ->orderBy('hora_inicio', 'asc')
            ->get();
    }

    /**
     *
     * @param Reserva $reserva
     * @param int $horasAntelacionMinimas
     * @return bool
     */
    public function cancelarReserva(Reserva $reserva, int $horasAntelacionMinimas = 2): bool
    {
        Log::info("ReservaService: Attempting to cancel reservation ID: {$reserva->reserva_id}");
        try {

            $fechaHoraInicioReserva = $reserva->fecha->copy()->setTimeFromTimeString($reserva->hora_inicio);
            $ahora = Carbon::now();

            // --- Validación  ---
            if ($ahora->gte($fechaHoraInicioReserva)) {
                Log::warning("ReservaService: Attempt to cancel reservation ID {$reserva->reserva_id} that has already started or passed.");
                return false;
            }
            if ($ahora->diffInHours($fechaHoraInicioReserva) < $horasAntelacionMinimas) {
                Log::warning("ReservaService: Attempt to cancel reservation ID {$reserva->reserva_id} within the minimum notice period ({$horasAntelacionMinimas} hours).");
                return false;
            }
            // --- Fin Validación ---


            // Verifica si ya está cancelada
            if ($reserva->estado === 'Cancelada') {
                Log::info("ReservaService: Reservation ID {$reserva->reserva_id} is already cancelled.");
                return true; // Ya estaba cancelada
            }

            // Actualiza el estado
            $reserva->estado = 'Cancelada';
            $success = $reserva->save();

            if ($success) {
                Log::info("ReservaService: Reservation ID {$reserva->reserva_id} cancelled successfully.");
                // TODO: disparar un evento enviar noti
            } else {
                Log::error("ReservaService: Failed to save cancellation for reservation ID {$reserva->reserva_id}.");
            }
            return $success;

        } catch (\Exception $e) {
            Log::error("ReservaService: Error cancelling reservation ID {$reserva->reserva_id}: " . $e->getMessage());
            return false;
        }
    }

    public function findReservaById(int $reservaId): ?Reserva
    {
        try {
            $reserva = Reserva::where('reserva_id', $reservaId)->first();
            if (!$reserva) {
                Log::warning("ReservaService: No reservation found with ID {$reservaId}");
                return null;
            }
            return $reserva;
        } catch (\Exception $e) {
            Log::error("ReservaService: Error finding reservation by ID {$reservaId}: " . $e->getMessage());
            return null;
        }
    }
}