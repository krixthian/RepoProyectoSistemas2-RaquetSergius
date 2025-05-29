<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use Illuminate\Support\Facades\Log;

class MenuInfoClubHandler implements IntentHandlerInterface
{
    public function handle(array $parameters, string $senderId, ?string $action = null): array
    {
        Log::info("[MenuInfoClubHandler] Executing for senderId: {$senderId}");

        $textoMenu = 'Selecciona qué información del club deseas ver:';
        $botones = [
            // Los IDs deben ser frases que activen los intents correspondientes
            ['id' => 'Direccion del club', 'title' => '📍 Dirección'],      // Activa Menu_Info_Direccion
            ['id' => 'Sobre nosotros club', 'title' => '📝 Sobre Nosotros'], // Activa Menu_Info_SobreNosotros
            ['id' => 'Contacto de personal', 'title' => '📞 Contacto Personal'] // Activa Menu_Info_Contacto
        ];

        // Contexto para indicar que esperamos una selección de info general
        $outputContexts = [];
        // $projectId = trim(config('dialogflow.project_id'), '/');
        // $sessionId = 'whatsapp-' . $senderId;
        // $outputContexts[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/info_club_esperando_opcion", 'lifespanCount' => 2];

        return [
            'fulfillmentText' => $textoMenu,
            'message_type' => 'interactive_buttons',
            'payload' => ['buttons' => $botones, 'header' => 'Información del Club'],
            'outputContextsToSetActive' => $outputContexts
        ];
    }
}