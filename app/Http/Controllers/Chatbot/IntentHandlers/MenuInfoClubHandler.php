<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;

class MenuInfoClubHandler implements IntentHandlerInterface
{
    public function handle(array $parameters, string $senderId): array
    {
        return [
            'type' => 'interactive_buttons',
            'header' => 'Información del Club',
            'text' => 'Selecciona qué información deseas ver:',
            'buttons' => [
                ['id' => 'menu_select_direccion', 'title' => '📍 Dirección'],
                ['id' => 'menu_select_sobre_nosotros', 'title' => '📝 Sobre Nosotros'],
                ['id' => 'menu_select_contacto_directo', 'title' => '📞 de Personal']
            ]
        ];
    }
}