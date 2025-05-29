<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use Illuminate\Support\Facades\Log;

class MenuContactoDirectoHandler implements IntentHandlerInterface
{
    public function handle(array $parameters, string $senderId, ?string $action = null): array
    {
        Log::info("[MenuContactoDirectoHandler] Executing for senderId: {$senderId}");
        $numeroPersonalText = "¡Claro! Puedes comunicarte con nosotros al número de teléfono: 2 2418133 o al celular 61119996 para atención personalizada.";

        return [
            'fulfillmentText' => $numeroPersonalText,
            'message_type' => 'text',
            'payload' => [],
            'outputContextsToSetActive' => [] // No se necesitan contextos después de esto
        ];
    }
}