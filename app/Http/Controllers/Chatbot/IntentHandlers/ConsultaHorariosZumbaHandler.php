<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Models\ClaseZumba;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ConsultaHorariosZumbaHandler implements IntentHandlerInterface
{
    /**
     * Maneja la consulta de horarios de Zumba.
     *
     * @param array $parameters
     * @param string $senderId
     * @return string
     */
    public function handle(array $parameters, string $senderId): string
    {
        Log::info('Executing ConsultaHorariosZumbaHandler');
        Carbon::setLocale('es');
        // --- Lógica para consultar horarios ---

        // Por ahora, buscaremos las clases de los próximos 7 días
        $hoy = Carbon::today();
        $fechaLimite = Carbon::today()->addDays(7);

        try {
            // Consultar clases futuras, ordenadas por fecha/hora
            // Eager loading para obtener datos del instructor y área si es necesario
            $clases = ClaseZumba::where('fecha_hora_inicio', '>=', $hoy)
                ->where('fecha_hora_inicio', '<', $fechaLimite)
                ->with(['instructor', 'area']) // Carga relaciones si las necesitas en la respuesta
                ->orderBy('fecha_hora_inicio', 'asc')
                ->get();

            if ($clases->isEmpty()) {
                return "Actualmente no tenemos clases de Zumba programadas para los próximos 7 días.";
            }

            // Formatear la respuesta
            $responseText = "Estos son los horarios de Zumba para los próximos 7 días:\n\n";
            $diaActual = null;

            foreach ($clases as $clase) {
                $fechaInicio = Carbon::parse($clase->fecha_hora_inicio);
                $fechaFin = Carbon::parse($clase->fecha_hora_fin);
                $nombreDia = $fechaInicio->isoFormat('dddd D'); // Ej: "Martes 22"

                // Agrupa por día
                if ($nombreDia !== $diaActual) {
                    $responseText .= "\n--- {$nombreDia} ---\n";
                    $diaActual = $nombreDia;
                }

                $horaInicioStr = $fechaInicio->format('H:i');
                $horaFinStr = $fechaFin->format('H:i');
                $instructorNombre = $clase->instructor ? $clase->instructor->nombre : 'Instructor por confirmar'; // Usa el nombre del instructor si la relación funcionó
                $areaNombre = $clase->area ? $clase->area->nombre : 'Área no especificada'; // Usa el nombre del área si la relación funcionó
                // $cupoDisponible = $clase->cupo_maximo - $clase->cupo_actual; // Opcional: Mostrar cupos

                $responseText .= "- {$horaInicioStr} a {$horaFinStr} con {$instructorNombre} (en {$areaNombre})\n";
                // $responseText .= "   (Cupos disponibles: {$cupoDisponible})\n"; // Opcional
            }

            $responseText .= "\nSi deseas inscribirte en alguna, házmelo saber.";

            return $responseText;

        } catch (\Exception $e) {
            Log::error("Error querying Zumba classes: " . $e->getMessage());
            return "Lo siento, ocurrió un error al consultar los horarios de Zumba. Por favor, intenta más tarde.";
        }
    }
}