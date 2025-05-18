<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;

class MenuContactoDirectoHandler implements IntentHandlerInterface
{
    public function handle(array $parameters, string $senderId): array
    {
        // Reutiliza la lógica de 'comunicar recepcion' que tenías en whatsappController
        $numeroPersonalText = "¡Claro! Puedes comunicarte con nosotros al número de teléfono: +591 2 2418133 o al celular/WhatsApp +591 61119996 para atención personalizada.";
        return ['fulfillmentText' => $numeroPersonalText];
    }
}