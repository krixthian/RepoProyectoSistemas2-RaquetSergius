<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use Illuminate\Support\Facades\Log;

class MenuPrincipalHandler implements IntentHandlerInterface
{
    public function __construct()
    {
    }

    public function handle(array $parameters, string $senderId, ?string $action = null): array
    {
        Log::info("[MenuPrincipalHandler] Executing for senderId: " . $senderId);

        $messagesToSend = [];

        // Mensaje de bienvenida
        $messagesToSend[] = [
            'fulfillmentText' => "Hola! 👋 Soy tu asistente virtual del Club Raquet Sergius. Elige una categoría para continuar:",
            'message_type' => 'text',
            'payload' => [],
        ];

        // Primer grupo de botones
        $botonesParte1 = [
            ['id' => 'Wally', 'title' => '🏐 Wally'],          // Usuario dice/presiona "Wally" -> Activa intent Menu_Submenu_Wally (action: menu.wally)
            ['id' => 'Zumba', 'title' => '💃 Zumba'],          // Usuario dice/presiona "Zumba" -> Activa intent Menu_Submenu_Zumba (action: menu.zumba)
            // ['id' => 'Puntos', 'title' => '🏆 Puntos'],     // Para el futuro
        ];
        $messagesToSend[] = [
            'fulfillmentText' => "Categorías principales:", // Este texto es para el cuerpo del mensaje de botones
            'message_type' => 'interactive_buttons',
            'payload' => [
                // 'header' => 'Categorías', // Opcional
                'buttons' => $botonesParte1
            ],
        ];

        // Segundo grupo de botones (enviado como un nuevo mensaje de botones)
        $botonesParte2 = [
            ['id' => 'Informacion general', 'title' => 'ℹ️ Info. General'], // Activa intent Menu_Submenu_Info (action: menu.info)
            ['id' => 'Mis datos', 'title' => '👤 Mis Datos'],         // Activa intent Menu_Submenu_MisDatos (action: menu.misDatos)
        ];
        $messagesToSend[] = [
            'fulfillmentText' => "Más opciones:", // Cuerpo para el segundo mensaje de botones
            'message_type' => 'interactive_buttons',
            'payload' => [
                'buttons' => $botonesParte2
            ],
        ];

        // Contextos para Dialogflow: Indicar que el usuario está en el menú principal
        // y esperamos una selección de estas categorías.
        $outputContexts = [];
        // Podrías definir un contexto como 'menu_principal_esperando_seleccion_categoria'
        // para que los intents de los submenús lo tengan como entrada.
        // $outputContexts = $this->generarNombresContextosActivos(['menu_principal_esperando_seleccion_categoria']);

        return [
            'messages_to_send' => $messagesToSend,
            'outputContextsToSetActive' => $outputContexts // Opcional, si es necesario
        ];
    }
    // Podrías necesitar las funciones generarNombresContextosActivos y generarNombresContextosActivosParaLimpiar aquí
    // si necesitas gestionar contextos desde este handler. Las copias del Orquestador podrían adaptarse.
}