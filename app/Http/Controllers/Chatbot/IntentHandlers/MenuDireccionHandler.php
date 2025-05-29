<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use Illuminate\Support\Facades\Log;

class MenuDireccionHandler implements IntentHandlerInterface
{
    public function handle(array $parameters, string $senderId, ?string $action = null): array
    {
        Log::info("[MenuDireccionHandler] Executing for senderId: {$senderId}");

        $locationName = 'Raquet Sergius Club';
        $locationAddress = 'Calle Ascarrunz #2564, Sopocachi, La Paz';
        $latitude = -16.512638;  // Latitud de Raquet Sergius Club
        $longitude = -68.122094; // Longitud de Raquet Sergius Club

        // Primero un mensaje de texto y luego la ubicación
        $messagesToSend = [
            [
                'fulfillmentText' => "Aquí tienes nuestra dirección: {$locationName}, {$locationAddress}. También te envío la ubicación en el mapa.",
                'message_type' => 'text',
                'payload' => [],
            ],
            [
                'fulfillmentText' => $locationName, // El texto no se usa directamente para el tipo 'location' por Meta
                'message_type' => 'location',
                'payload' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'name' => $locationName,
                    'address' => $locationAddress
                ],
            ]
        ];

        // No se necesitan contextos específicos para el siguiente turno usualmente después de dar info.
        // Se podría limpiar el contexto de 'info_club_esperando_opcion' si lo hubieras establecido.
        $outputContexts = [];
        // $projectId = trim(config('dialogflow.project_id'), '/');
        // $sessionId = 'whatsapp-' . $senderId;
        // $outputContexts[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/info_club_esperando_opcion", 'lifespanCount' => 0]; // Limpiar

        return [
            // Como ahora `whatsappController` espera un array `messages_to_send`,
            // este handler puede devolver múltiples mensajes si es necesario.
            'messages_to_send' => $messagesToSend,
            'outputContextsToSetActive' => $outputContexts
        ];
    }
}