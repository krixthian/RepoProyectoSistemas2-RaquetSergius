<?php

namespace App\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Models\Cancha; // Importa el Model necesario
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ConsultaDisponibilidadCanchaHandler implements IntentHandlerInterface
{
    public function handle(array $parameters, string $senderId): string
    {
        Log::info('Executing ConsultaDisponibilidadCanchaHandler');
        // --- Lógica que estaba en el case 'Consulta Disponibilidad Cancha' ---

        //parametros de Dialogflow
        $tipoCancha = $parameters['tipo_cancha'] ?? 'wally';
        $fecha = $parameters['date-time'] ?? null;
        $hora = $parameters['hora'] ?? null;
        $diasemana = $parameters['date-time'] ?? null;
        $wally = $parameters['date-time'] ?? null;
        $reserva = $parameters['date-time'] ?? null;

        $fechaConsulta = now();

        if ($fecha && isset($fecha['date_time'])) {
            try {
                $fechaConsulta = Carbon::parse($fecha['date_time']);
                Log::info("Handler - Fecha/hora consultada: " . $fechaConsulta->toDateTimeString());
            } catch (\Exception $dateError) {
                Log::warning("Handler - No se pudo parsear la fecha de Dialogflow: " . json_encode($fecha));
                // Podrías devolver un mensaje de error o continuar con la fecha actual
            }
        }

        $query = Cancha::where('disponible', true);
        if ($tipoCancha) {
            $query->whereRaw('LOWER(tipo) LIKE ?', ['%' . strtolower($tipoCancha) . '%']);
        }
        // Añadir lógica de fecha/hora aquí si es necesario

        $canchas = $query->orderBy('nombre')->get();

        if ($canchas->isEmpty()) {
            $responseText = "Lo siento, no encontré canchas de tipo '{$tipoCancha}' disponibles en este momento.";
        } else {
            $responseText = "Canchas de '{$tipoCancha}' disponibles:\n";
            foreach ($canchas as $cancha) {
                $responseText .= "- {$cancha->nombre} (Precio: {$cancha->precio_hora} Bs/hora)\n";
            }
            $responseText .= "\nSi quieres reservar, por favor indica la cancha, fecha y hora.";
        }
        // --- Fin de la lógica del case ---

        return $responseText;
    }
}