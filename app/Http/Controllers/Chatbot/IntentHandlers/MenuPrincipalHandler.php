<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;

class MenuPrincipalHandler implements IntentHandlerInterface
{
    public function handle(array $parameters, string $senderId): array
    {
        return [
            'type' => 'interactive_buttons',
            'header' => 'Menú Principal',
            'text' => 'Hola! Soy tu asistente virtual de Raquet Sergius. ¿En qué puedo ayudarte?',
            'buttons' => [
                ['id' => 'menu_select_mis_datos', 'title' => '👤 Mis Datos'],
                ['id' => 'menu_select_info_club', 'title' => 'ℹ️ Info del Club'],
            ]
        ];
    }
}