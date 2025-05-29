<?php // app/Http/Controllers/Chatbot/IntentHandlers/MenuInfoGeneralHandler.php
namespace App\Http\Controllers\Chatbot\IntentHandlers;
use App\Chatbot\IntentHandlerInterface;
use Illuminate\Support\Facades\Log;

class MenuInfoGeneralHandler implements IntentHandlerInterface
{
    public function handle(array $parameters, string $senderId, ?string $action = null): array
    {
        Log::info("[MenuInfoGeneralHandler] Executing for senderId: {$senderId}");
        $textoMenu = 'Selecciona qué información del club deseas ver:';
        $botones = [
            ['id' => 'menu_select_direccion', 'title' => '📍 Dirección'],
            ['id' => 'menu_select_sobre_nosotros', 'title' => '📝 Sobre Nosotros'],
            ['id' => 'menu_select_contacto_directo', 'title' => '📞 Contacto Personal']
        ];
        return [
            'messages_to_send' => [
                [
                    'fulfillmentText' => $textoMenu,
                    'message_type' => 'interactive_buttons',
                    'payload' => ['buttons' => $botones, 'header' => 'Información General']
                ]
            ],
            'outputContextsToSetActive' => [] // Podrías poner 'info_general_esperando_opcion'
        ];
    }
}