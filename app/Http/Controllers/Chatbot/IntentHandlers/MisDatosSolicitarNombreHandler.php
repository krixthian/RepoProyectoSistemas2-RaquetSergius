<?php // MisDatosSolicitarNombreHandler.php
namespace App\Http\Controllers\Chatbot\IntentHandlers;
use App\Chatbot\IntentHandlerInterface;

class MisDatosSolicitarNombreHandler implements IntentHandlerInterface
{
    public function handle(array $parameters, string $senderId): array
    {
        // Aquí podríamos guardar un estado temporal, ej: Cache::put('user_state:'.$senderId, 'awaiting_misdatos_nombre', 300);
        return ['fulfillmentText' => 'Por favor, escribe tu nombre completo:'];
    }
}