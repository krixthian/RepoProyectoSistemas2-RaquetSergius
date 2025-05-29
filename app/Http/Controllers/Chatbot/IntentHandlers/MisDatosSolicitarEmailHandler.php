<?php
namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use Illuminate\Support\Facades\Log;

class MisDatosSolicitarEmailHandler implements IntentHandlerInterface
{
    public function handle(array $parameters, string $senderId, ?string $action = null): array
    {
        Log::info("[MisDatosSolicitarEmailHandler] Executing for senderId: {$senderId}");
        $mensaje = "Por favor, ingresa tu nueva dirección de correo electrónico:";

        $outputContextsToSetActive = [];
        $projectId = trim(config('dialogflow.project_id'), '/');
        $sessionId = 'whatsapp-' . $senderId;
        if ($projectId) {
            $outputContextsToSetActive[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/mis_datos_esperando_email_captura", 'lifespanCount' => 2];
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