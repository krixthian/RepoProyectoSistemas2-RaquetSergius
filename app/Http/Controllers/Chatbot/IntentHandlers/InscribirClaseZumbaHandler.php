<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ZumbaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InscribirClaseZumbaHandler implements IntentHandlerInterface
{
    protected ZumbaService $zumbaService;

    public function __construct(ZumbaService $zumbaService)
    {
        $this->zumbaService = $zumbaService;
    }

    public function handle(array $parameters, string $senderId): string|array
    {
        if (empty($senderId)) {
            return ['fulfillmentText' => 'No hemos podido identificar tu número. Por favor, intenta de nuevo.'];
        }
        $telefonoClienteNormalizado = $this->normalizePhoneNumber($senderId);

        if (empty($parameters['fecha']) || empty($parameters['hora'])) {
            return ['fulfillmentText' => 'Por favor, especifica el día y la hora de la clase de Zumba. (Faltan fecha u hora).'];
        }

        try {
            $fechaClaseCarbon = Carbon::parse($parameters['fecha']);
            $diaSemana = ucfirst($fechaClaseCarbon->locale('es_ES')->dayName);

            $horaClaseCarbon = Carbon::parse($parameters['hora']);
            $horaInicioFormatBD = $horaClaseCarbon->format('H:i:s');

        } catch (\Exception $e) {
            Log::error('Error al parsear fecha u hora en InscribirClaseZumbaHandler: ' . $e->getMessage(), [
                'parameters' => $parameters,
                'senderId' => $senderId
            ]);
            return ['fulfillmentText' => 'El formato de la fecha u hora proporcionada no es válido.'];
        }


        $datosClienteAdicionales = ['nombre' => $parameters['user_profile_name'] ?? null];
        $datosClienteAdicionales = [];


        // Llamar al servicio
        $resultado = $this->zumbaService->inscribirClienteAClase(
            $telefonoClienteNormalizado,
            $diaSemana,
            $horaInicioFormatBD,
            $datosClienteAdicionales
        );

        return ['fulfillmentText' => $resultado['message']];
    }

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        if (strpos($phoneNumber, 'whatsapp:+') === 0) {
            return substr($phoneNumber, strlen('whatsapp:+'));
        }
        return preg_replace('/[^0-9+]/', '', $phoneNumber);
    }
}