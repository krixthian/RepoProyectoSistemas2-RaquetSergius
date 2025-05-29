<?php
namespace App\Http\Controllers\Chatbot\IntentHandlers;
use App\Chatbot\IntentHandlerInterface;
use Illuminate\Support\Facades\Log;

class MenuZumbaHandler implements IntentHandlerInterface
{
    public function handle(array $parameters, string $senderId, ?string $action = null): array
    {
        Log::info("[MenuZumbaHandler] Executing for senderId: {$senderId}");
        $textoSubmenu = "Opciones de Zumba:";
        $botones = [
            ['id' => 'Consultar horarios de Zumba', 'title' => '📅 Consultar Horarios'], // Activa Consulta Horarios Zumba
            ['id' => 'Inscribirme a Zumba', 'title' => '✍️ Inscribirme'], // Activa Inscribir Clase Zumba (FUTURO)
            ['id' => 'Cancelar inscripcion Zumba', 'title' => '🚫 Cancelar Inscripc.'], // (FUTURO)
        ];
        return [
            'messages_to_send' => [
                [
                    'fulfillmentText' => $textoSubmenu,
                    'message_type' => 'interactive_buttons',
                    'payload' => ['buttons' => $botones]
                ]
            ],
            'outputContextsToSetActive' => [] // Podrías poner un contexto 'submenu_zumba_activo'
        ];
    }
}