<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
// Accede a las constantes definidas en whatsappController
use App\Http\Controllers\Chatbot\whatsappController;


class MenuDireccionHandler implements IntentHandlerInterface
{
    public function handle(array $parameters, string $senderId): array
    {

        $locationName = 'Raquet Sergius Club';
        $locationAddress = 'Calle Ascarrunz #2564, Sopocachi, La Paz';
        $latitude = -16.512638;
        $longitude = -68.122094;


        return [
            'type' => 'location',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'name' => $locationName,
            'address' => $locationAddress
        ];
    }
}