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
}