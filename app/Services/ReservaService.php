<?php

namespace App\Services;

use App\Models\Reserva; // Importa el modelo necesario
use Carbon\Carbon;
use Illuminate\Support\Facades\Log; // Opcional, para logging si es necesario

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
            // Validar o asegurar el formato Y-m-d si es necesario, aunque Carbon::parse es flexible
            $fecha = Carbon::parse($fechaString)->format('Y-m-d');

            $reservas = Reserva::whereDate('fecha', $fecha)
                ->where('estado', 'Confirmada')
                ->get(['hora_inicio', 'hora_fin', 'estado', 'cancha_id']) // Asegúrate que 'fecha' está en la tabla reservas
                ->toArray(); // Convertir a array para desacoplar del Eloquent Collection si se prefiere

            return $reservas;

        } catch (\Exception $e) {
            Log::error("Error en ReservaService al obtener reservas para fecha {$fechaString}: " . $e->getMessage());
            return null; // Retorna null para indicar un error
        }
    }

    // Puedes añadir más métodos relacionados con la lógica de reservas aquí
    // public function crearReserva(...) {}
    // public function cancelarReserva(...) {}
    // etc.
}