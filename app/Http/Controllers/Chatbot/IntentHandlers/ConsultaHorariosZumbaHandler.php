<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use App\Models\AreaZumba; // Make sure this model exists and is correctly namespaced
use Carbon\Carbon;

class ConsultaHorariosZumbaHandler implements IntentHandlerInterface
{
    public function __construct()
    {
        // Constructor si necesitas servicios
    }

    public function handle(array $parameters, string $senderId, ?string $action = null): array
    {
        Log::info("[ConsultaHorariosZumbaHandler] Executing for senderId: " . $senderId);
        Carbon::setLocale('es'); // Set locale for Carbon date formatting

        $areaZumba = AreaZumba::where('disponible', true)->with('clases')->first();
        $caption = "Estos son nuestros horarios de Zumba!!!\nNota. Todas las clases son de 1 hora.\n"; // Initialize caption
        $imageUrl = null;

        if (!$areaZumba) {
            Log::warning('[ConsultaHorariosZumbaHandler] No available Zumba areas found or no classes associated.');
            $caption .= "\n(Actualmente no tenemos áreas de zumba o clases programadas. Por favor, consulta más tarde.)";
        } else {
            // Attempt to get image URL from the AreaZumba model if it has a specific image
            // Assuming your AreaZumba model has a 'ruta_imagen' attribute that stores the relative path from public/
            if (!empty($areaZumba->ruta_imagen)) {
                $publicPathForAreaImage = $areaZumba->ruta_imagen; // e.g., 'images/zumba_area_1.jpg'
                if (file_exists(public_path($publicPathForAreaImage))) {
                    $imageUrl = URL::asset($publicPathForAreaImage);
                    Log::info("[ConsultaHorariosZumbaHandler] Generated image URL from AreaZumba: " . $imageUrl);
                } else {
                    Log::warning("[ConsultaHorariosZumbaHandler] Image {$publicPathForAreaImage} from AreaZumba not found in public path.");
                }
            }

            // Fallback to a generic image if no specific image for the area is found or set
            if (!$imageUrl) {
                $genericImagePublicPath = 'images/horarios_zumba.jpg'; // Ruta relativa a tu carpeta public/
                if (file_exists(public_path($genericImagePublicPath))) {
                    $imageUrl = URL::asset($genericImagePublicPath);
                    Log::info("[ConsultaHorariosZumbaHandler] Generated generic public image URL: " . $imageUrl);
                } else {
                    Log::warning("[ConsultaHorariosZumbaHandler] Generic image {$genericImagePublicPath} no encontrada en la carpeta public.");
                }
            }

            $clases = $areaZumba->clases() // Assuming 'clases' is a correctly defined relationship
                ->orderBy('diasemama') // Consider converting diasemana to a sortable index if it's text
                ->orderBy('hora_inicio')
                ->get();

            $horariosNoHabilitados = [];

            if ($clases->isEmpty()) {
                $caption .= "\n(Actualmente no hay clases programadas para esta área)";
            } else {
                foreach ($clases as $clase) {
                    // Ensure 'habilitado' attribute exists and is a boolean or can be evaluated as such
                    if (isset($clase->habilitado) && ($clase->habilitado === false || $clase->habilitado === 0 || $clase->habilitado === null)) {
                        $dia = $clase->diasemama ?? 'Día no especificado';
                        try {
                            $horaInicio = Carbon::parse($clase->hora_inicio)->format('H:i');
                            $horaFin = Carbon::parse($clase->hora_fin)->format('H:i');
                            $horariosNoHabilitados[] = "- El horario de {$dia} de {$horaInicio} a {$horaFin}";
                        } catch (\Exception $e) {
                            Log::error("[ConsultaHorariosZumbaHandler] Error parsing class time: " . $e->getMessage());
                            $horariosNoHabilitados[] = "- Horario de {$dia} con formato de hora inválido.";
                        }
                    }
                }
            }

            if (!empty($horariosNoHabilitados)) {
                $caption .= "\n\n*Aviso:* Los siguientes horarios podrían no estar habilitados temporalmente:\n";
                $caption .= implode("\n", $horariosNoHabilitados);
            } elseif (!$clases->isEmpty()) { // Only add this if there were classes to check
                $caption .= "\n\nTodos nuestros horarios listados en la imagen están habilitados actualmente.";
            }
        }

        // $mensajeBase ya no se usa como antes, $caption tiene toda la info de texto.
        // Si $imageUrl existe, el $caption se usará como caption de la imagen.
        // Si $imageUrl no existe, $caption será el fulfillmentText.

        $messages = [];
        if ($imageUrl) {
            $messages[] = [
                'fulfillmentText' => $caption, // El caption también puede ir en el payload
                'message_type' => 'image',
                'payload' => [
                    'image_url' => $imageUrl,
                    'caption' => $caption
                ]
            ];
        } else {
            $messages[] = [
                'fulfillmentText' => $caption,
                'message_type' => 'text',
                'payload' => []
            ];
        }

        return [
            'messages_to_send' => $messages,
            'outputContextsToSetActive' => [] // Generalmente no se necesitan aquí
        ];
    }
}