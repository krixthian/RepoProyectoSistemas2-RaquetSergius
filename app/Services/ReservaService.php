<?php

namespace App\Services;

use App\Models\Reserva;
use App\Models\Cliente; // Necesario si haces validaciones de cliente aquí
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException; // Para capturar errores de BD

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
     * Crea una nueva reserva en la base de datos.
     *
     * @param array $datosReserva Array con los datos validados para la reserva.
     * Ej: ['cliente_id'=>1, 'cancha_id'=>2, 'fecha'=>'2025-04-15', 'hora_inicio'=>'17:00:00', ...]
     * @return Reserva|null Retorna el modelo Reserva creado o null si falla.
     */
    public function crearReserva(array $datosReserva): ?Reserva
    {
        Log::info("ReservaService: Attempting to create reservation with data: " . json_encode($datosReserva));
        try {
            // Asegurarse que los campos coincidan con el $fillable del Modelo Reserva
            $nuevaReserva = Reserva::create($datosReserva);
            Log::info("ReservaService: Reservation created successfully. ID: " . $nuevaReserva->reserva_id);
            return $nuevaReserva;

        } catch (QueryException $qe) { // Error específico de BD
            Log::error("ReservaService: Database query error creating reservation: " . $qe->getMessage(), $datosReserva);
            return null;
        } catch (\Exception $e) { // Otro tipo de error
            Log::error("ReservaService: General error creating reservation: " . $e->getMessage(), $datosReserva);
            return null;
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