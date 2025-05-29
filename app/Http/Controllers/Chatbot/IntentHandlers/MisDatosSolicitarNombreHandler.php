<?php // app/Http/Controllers/Chatbot/IntentHandlers/MisDatosSolicitarNombreHandler.php
namespace App\Http\Controllers\Chatbot\IntentHandlers;
use App\Chatbot\IntentHandlerInterface;
use Illuminate\Support\Facades\Log;

class MisDatosSolicitarNombreHandler implements IntentHandlerInterface
{
    public function handle(array $parameters, string $senderId, ?string $action = null): array
    {
        Log::info("[MisDatosSolicitarNombreHandler] Executing for senderId: {$senderId}");
        $mensaje = "¿Cuál es tu nombre completo, por favor?";

        $outputContextsToSetActive = [];
        $projectId = trim(config('dialogflow.project_id'), '/');
        $sessionId = 'whatsapp-' . $senderId; // Asumimos senderId ya normalizado
        if ($projectId) {
            $outputContextsToSetActive[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/mis_datos_esperando_nombre_captura", 'lifespanCount' => 2];
        }

        return [
            'messages_to_send' => [
                [
                    'fulfillmentText' => $mensaje,
                    'message_type' => 'text',
                    'payload' => []
                ]
            ],
            'outputContextsToSetActive' => $outputContextsToSetActive
        ];
    }
}