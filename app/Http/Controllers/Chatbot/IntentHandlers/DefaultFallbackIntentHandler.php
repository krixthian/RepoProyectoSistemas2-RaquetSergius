<?php // app/Http/Controllers/Chatbot/IntentHandlers/DefaultFallbackIntentHandler.php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use Illuminate\Support\Facades\Log;

class DefaultFallbackIntentHandler implements IntentHandlerInterface
{
    public function handle(array $parameters, string $senderId, ?string $action = null): array
    {
        $queryText = "lo que dijiste"; // Default
        // Intentar obtener el texto original de diferentes maneras
        if (isset($parameters['queryResult']['queryText']) && !empty($parameters['queryResult']['queryText'])) {
            $queryText = $parameters['queryResult']['queryText'];
        } elseif (isset($parameters['text']) && !empty($parameters['text'])) { // Si pasaras el texto original en los params
            $queryText = $parameters['text'];
        }

        Log::info("[DefaultFallbackIntentHandler] Executing for senderId: {$senderId}. Query: " . $queryText);

        $fallbackMsg = "Lo siento, no entendí \"{$queryText}\". ¿Podrías intentarlo de otra manera o escribir 'menú' para ver las opciones?";
        // Usar el fulfillment de Dialogflow si está disponible y es mejor
        if (isset($parameters['queryResult']['fulfillmentText']) && !empty($parameters['queryResult']['fulfillmentText'])) {
            $fallbackMsg = $parameters['queryResult']['fulfillmentText'];
        }

        // Para el fallback, generalmente no se establecen contextos nuevos.
        // Podrías usar los outputContexts que Dialogflow mismo devuelve para el fallback,
        // o decidir no enviar ninguno para no interferir.
        $outputContextsDialogflow = $parameters['queryResult']['outputContexts'] ?? [];

        return [
            'messages_to_send' => [
                [
                    'fulfillmentText' => $fallbackMsg,
                    'message_type' => 'text',
                    'payload' => []
                ]
            ],
            // Decidir si pasar los contextos que Dialogflow generó o un array vacío.
            // Pasar los de Dialogflow puede ser útil si tiene lógica de reprompt.
            'outputContextsToSetActive' => $outputContextsDialogflow
        ];
    }
}