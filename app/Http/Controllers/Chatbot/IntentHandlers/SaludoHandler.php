<?php // app/Http/Controllers/Chatbot/IntentHandlers/SaludoHandler.php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SaludoHandler implements IntentHandlerInterface
{
    public function handle(array $parameters, string $senderId, ?string $action = null): array
    {
        Log::info("[SaludoHandler] Executing for senderId: {$senderId}");

        $saludoMsg = "¡Hola! Bienvenido a Raquet-Sergius. ¿En qué puedo ayudarte hoy? También puedes escribir 'menú' para ver las opciones.";
        // Si Dialogflow provee un fulfillmentText para Saludo, podrías usarlo:
        // $saludoMsg = $parameters['queryResult']['fulfillmentText'] ?? $saludoMsg;
        // (Necesitarías pasar queryResult a los parámetros si quieres hacer esto)

        // Limpiar caché de flujos activos al saludar
        $cacheKeyReserva = 'reserva_cancha_' . $senderId;
        Cache::forget($cacheKeyReserva);
        // Cache::forget('otro_flujo_cache_' . $senderId); // etc.

        // Limpiar contextos de Dialogflow relacionados con flujos
        $outputContextsToSetActive = [];
        $projectId = trim(config('dialogflow.project_id'), '/');
        $sessionId = 'whatsapp-' . $senderId;
        $contextsToClear = ['reserva_cancha_en_progreso', 'reserva_cancha_esperando_fecha', 'reserva_cancha_esperando_hora_inicio', /* añade otros contextos de flujo */];
        foreach ($contextsToClear as $ctxName) {
            $outputContextsToSetActive[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/{$ctxName}", 'lifespanCount' => 0];
        }

        return [
            'messages_to_send' => [
                [
                    'fulfillmentText' => $saludoMsg,
                    'message_type' => 'text',
                    'payload' => []
                ]
            ],
            'outputContextsToSetActive' => $outputContextsToSetActive
        ];
    }
}