<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cancha;
use App\Models\Reserva;
use Carbon\Carbon;

class DisponibilidadController extends Controller
{
    /**
     * Muestra la vista de disponibilidad semanal de las canchas.
     */
    public function index(Request $request)
    {
        \Carbon\Carbon::setLocale('es');
        // 1. Determinar la fecha para la vista (si no se especifica, usa la fecha actual)
        // Carbon es una librería fantástica para manejar fechas y horas en PHP.
        $fechaSeleccionada = Carbon::parse($request->query('date', 'now'));

        // 2. Calcular el inicio y el fin de la semana para la fecha seleccionada
        $inicioSemana = $fechaSeleccionada->copy()->startOfWeek(Carbon::MONDAY);
        $finSemana = $fechaSeleccionada->copy()->endOfWeek(Carbon::SUNDAY);

        // 3. Generar un array con todas las fechas de la semana para usarlas en la vista
        $diasDeLaSemana = [];
        for ($date = $inicioSemana->copy(); $date->lte($finSemana); $date->addDay()) {
            $diasDeLaSemana[] = $date->copy();
        }

        // 4. Obtener todas las canchas
        $canchas = Cancha::orderBy('nombre')->get(); // Asumo que tienes una columna 'nombre'

        // 5. Obtener todas las reservas que caen en esa semana
        $reservas = Reserva::whereBetween('fecha', [$inicioSemana, $finSemana])
                           ->where('estado', '!=', 'Rechazada') // Opcional: excluir rechazadas
                           ->get();

        // 6. Organizar las reservas en un formato fácil de usar en la vista.
        // La clave será: [cancha_id][fecha][hora_inicio]
        $reservasAgrupadas = [];
        foreach ($reservas as $reserva) {
            $fechaKey = Carbon::parse($reserva->fecha)->format('Y-m-d');
            $horaKey = Carbon::parse($reserva->hora_inicio)->format('H:00:00');
            $reservasAgrupadas[$reserva->cancha_id][$fechaKey][$horaKey] = $reserva;
        }
        
        // 7. Definir el rango de horas a mostrar (ej. de 8:00 a 23:00)
        $horarios = range(8, 23);

        // 8. Pasar todos los datos a la vista
        return view('disponibilidad.index', [
            'canchas' => $canchas,
            'diasDeLaSemana' => $diasDeLaSemana,
            'horarios' => $horarios,
            'reservasAgrupadas' => $reservasAgrupadas,
            'inicioSemana' => $inicioSemana, // Para los botones de navegación
        ]);
    }
}