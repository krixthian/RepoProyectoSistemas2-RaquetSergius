<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Models\ClaseZumba;
use App\Models\AreaZumba;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL; // Importa el facade URL para usar asset() de forma explícita si prefieres
use Carbon\Carbon;

class ConsultaHorariosZumbaHandler implements IntentHandlerInterface
{
    /**
     * Maneja la consulta de horarios de Zumba.
     *
     * @param array $parameters
     * @param string $senderId
     * @return array|string // Compatible con la interfaz modificada
     */
    public function handle(array $parameters, string $senderId): array|string
    {
        Log::info('Executing ConsultaHorariosZumbaHandler');
        Carbon::setLocale('es');

        try {
            // 1. Obtener el Área de Zumba (misma lógica que antes)
            $areaZumba = AreaZumba::where('disponible', true)->with('clases')->first();

            if (!$areaZumba) {
                Log::warning('No available Zumba areas found.');
                return "Lo siento, no encontré áreas de Zumba disponibles en este momento.";
            }

            // Asegúrate que el campo 'ruta_imagen' NO esté vacío y contenga la RUTA RELATIVA a public/
            // Ejemplo: 'images/horarios_zumba.jpg'
            if (empty($areaZumba->ruta_imagen)) {
                Log::warning("AreaZumba ID {$areaZumba->area_id} does not have ruta_imagen set.");
                return "No encontré la imagen de horarios configurada. Por favor, contacta a administración.";
            }

            // *** MODIFICACIÓN: Construir URL pública usando asset() ***
            // asset() genera la URL completa: https://tu-dominio.com/ + la ruta relativa
            // Asegúrate que tu APP_URL en .env esté configurada correctamente.
            $publicImageUrl = asset($areaZumba->ruta_imagen);
            Log::info("Generated public image URL: " . $publicImageUrl);


            // 2. Obtener las clases y construir el caption (misma lógica que antes)
            $clases = $areaZumba->clases()
                ->orderBy('diasemama')
                ->orderBy('hora_inicio')
                ->get();

            $caption = "Estos son nuestros horarios de Zumba!!!\nNota. Todas las clases son de 1 hora.\n";
            $horariosNoHabilitados = [];

            if ($clases->isEmpty()) {
                $caption .= "\n(Actualmente no hay clases programadas para esta área)";
            } else {
                foreach ($clases as $clase) {
                    if ($clase->habilitado === false || $clase->habilitado === null) {
                        $dia = $clase->diasemama ?? 'Día no especificado';
                        $horaInicio = Carbon::parse($clase->hora_inicio)->format('H:i');
                        $horaFin = Carbon::parse($clase->hora_fin)->format('H:i');
                        $horariosNoHabilitados[] = "- El horario de {$dia} de {$horaInicio} a {$horaFin}";
                    }
                }
            }

            if (!empty($horariosNoHabilitados)) {
                $caption .= "\n\n*Aviso:* Los siguientes horarios podrían no estar habilitados temporalmente:\n";
                $caption .= implode("\n", $horariosNoHabilitados);
            } else {
                $caption .= "\n\nTodos nuestros horarios están habilitados actualmente.";
            }


            // 3. Retornar la estructura para enviar imagen con la URL pública
            return [
                'type' => 'image',
                'url' => $publicImageUrl, // <--- USA LA URL PÚBLICA GENERADA
                'caption' => $caption
            ];

        } catch (\Exception $e) {
            Log::error("Error in ConsultaHorariosZumbaHandler: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return "Lo siento, ocurrió un error al consultar los horarios de Zumba. Por favor, intenta más tarde.";
        }
    }
}