<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Models\Torneo; // Importa los Models necesarios
use App\Models\Evento;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InformacionTorneosHandler implements IntentHandlerInterface
{
    public function handle(array $parameters, string $senderId): string
    {
        Log::info('Executing InformacionTorneosHandler');
        // --- Lógica que estaba en el case 'Información e inscripcion Torneos' ---
        $torneos = Torneo::whereIn('estado', ['programado', 'en curso'])
            ->with('evento')
            ->get();

        if ($torneos->isEmpty()) {
            $responseText = "Actualmente no hay torneos programados o en curso.";
        } else {
            $responseText = "Próximos torneos y torneos en curso:\n";
            foreach ($torneos as $torneo) {
                $nombreEvento = $torneo->evento->nombre ?? 'Evento sin nombre';
                $fechaInicio = $torneo->evento ? Carbon::parse($torneo->evento->fecha_inicio)->format('d/m/Y') : 'N/D';
                $precio = $torneo->evento->precio_inscripcion ?? 0;
                $responseText .= "- {$nombreEvento} ({$torneo->deporte} - Cat: {$torneo->categoria}) - Inicio: {$fechaInicio} - Estado: {$torneo->estado} - Inscripción: {$precio} Bs.\n";
            }
            $responseText .= "\nSi deseas inscribirte o saber más de alguno, házmelo saber.";
        }
        // --- Fin de la lógica del case ---

        return $responseText;
    }
}