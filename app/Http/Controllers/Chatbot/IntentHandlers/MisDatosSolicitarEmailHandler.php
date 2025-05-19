<?php // MisDatosSolicitarEmailHandler.php
namespace App\Http\Controllers\Chatbot\IntentHandlers;
use App\Chatbot\IntentHandlerInterface;
// use Illuminate\Support\Facades\Cache;

class MisDatosSolicitarEmailHandler implements IntentHandlerInterface
{
    public function handle(array $parameters, string $senderId): array
    {
        // Determinar si el usuario dijo "no" al email previamente.
        // El payload del botón 'misdatos_solicitar_email_no' debería ser manejado aquí
        // o hacer que Dialogflow envíe un parámetro que lo indique.
        // Si el evento es 'misdatos_solicitar_email_no', entonces no preguntamos.
        $userResponseToPreviousQuestion = $parameters['triggering_event_or_payload'] ?? null; // Necesitarías configurar Dialogflow para que pase esto.

        if ($userResponseToPreviousQuestion === 'misdatos_solicitar_email_no') {
            // Cache::forget('user_state:'.$senderId);
            return ['fulfillmentText' => "Entendido. Tus datos han sido actualizados."];
        }

        // Si el usuario presionó "Modificar Email" o "Sí, agregar email"
        // Cache::put('user_state:'.$senderId, 'awaiting_misdatos_email', 300);
        return ['fulfillmentText' => "Por favor, escribe tu correo electrónico. Si no deseas proporcionarlo, escribe 'no'."];
    }
}