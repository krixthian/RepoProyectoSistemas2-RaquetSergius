<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use Illuminate\Support\Facades\Log;

class MenuSobreNosotrosHandler implements IntentHandlerInterface
{
    public function handle(array $parameters, string $senderId, ?string $action = null): array
    {
        Log::info("[MenuSobreNosotrosHandler] Executing for senderId: {$senderId}");
        $sobreNosotrosText = "Raquet Sergius Club es tu destino ideal para el deporte y bienestar en La Paz. Ofrecemos canchas de wally, clases de Zumba, torneos emocionantes y un ambiente amigable para toda la familia. ¡Visítanos y vive la experiencia Sergius!";

        return [
            'fulfillmentText' => $sobreNosotrosText,
            'message_type' => 'text',
            'payload' => [],
            'outputContextsToSetActive' => []
        ];
    }
}