<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use Illuminate\Support\Facades\Log;

class MenuWallyHandler implements IntentHandlerInterface
{
    public function handle(array $parameters, string $senderId, ?string $action = null): array
    {
        Log::info("[MenuWallyHandler] Executing for senderId: {$senderId}");

        $textoSubmenu = "Opciones de Wally (Reservas de Cancha):";
        $botones = [
            ['id' => 'Hacer una reserva', 'title' => '✅ Hacer Reserva'], // Activa ReservaCancha_Iniciar
            ['id' => 'Cancelar mi reserva', 'title' => '❌ Cancelar Reserva'], // Activa CancelarReserva_Iniciar
            ['id' => 'Cual es mi proxima reserva', 'title' => '🔍 Consultar Reserva']  // Activa Consulta Reserva
        ];

        $outputContexts = [];
        // Podrías establecer un contexto 'submenu_wally_activo' si los intents de reserva
        // necesitaran saber que vienen de este submenú específicamente.
        // O simplemente limpiar el contexto del menú principal.

        return [
            'messages_to_send' => [
                [
                    'fulfillmentText' => $textoSubmenu,
                    'message_type' => 'interactive_buttons',
                    'payload' => ['buttons' => $botones]
                ]
            ],
            'outputContextsToSetActive' => $outputContexts
        ];
    }
}