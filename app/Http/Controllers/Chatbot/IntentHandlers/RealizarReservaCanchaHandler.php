<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ClienteService;
use App\Services\ReservaService;
// Carbon ya no se necesita directamente aquí si los parámetros se pasan tal cual.
// use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RealizarReservaCanchaHandler implements IntentHandlerInterface
{
    protected ClienteService $clienteService;
    protected ReservaService $reservaService;

    public function __construct(ClienteService $clienteService, ReservaService $reservaService)
    {
        $this->clienteService = $clienteService;
        $this->reservaService = $reservaService;
    }

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        if (strpos($phoneNumber, 'whatsapp:+') === 0) {
            $phoneNumber = substr($phoneNumber, strlen('whatsapp:+'));
        }
        return preg_replace('/[^0-9]/', '', $phoneNumber);
    }

    public function handle(array $parameters, string $senderId): array
    {
        $telefonoCliente = $this->normalizePhoneNumber($senderId);

        // Usar los nombres de parámetros correctos de Dialogflow según tus logs
        $fechaIso = $parameters['fecha'] ?? null;
        $horaInicioIso = $parameters['horaini'] ?? null; // <--- CORREGIDO
        $duracionParam = $parameters['duracion'] ?? null;
        $horaFinParam = $parameters['horafin'] ?? null;   // <--- CORREGIDO

        Log::debug("[RealizarReservaCanchaHandler] Parámetros recibidos: ", $parameters);
        Log::debug("[RealizarReservaCanchaHandler] Sender ID: {$senderId}, Teléfono normalizado: {$telefonoCliente}");
        Log::debug("[RealizarReservaCanchaHandler] FechaISO: {$fechaIso}, HoraInicioISO: {$horaInicioIso}, Duracion: " . json_encode($duracionParam) . ", HoraFinISO: {$horaFinParam}");


        if (!$fechaIso || !$horaInicioIso) {
            return ['fulfillmentText' => 'Necesito la fecha y la hora de inicio para la reserva. Por favor, proporciona todos los datos.'];
        }
        // La validación de si hay duración O hora fin se hace mejor en el servicio,
        // que ya tiene una lógica para usar duración por defecto si ambas faltan.
        // if (!$duracionParam && !$horaFinParam) {
        //     Log::info("[RealizarReservaCanchaHandler] Ni duración ni hora_fin proporcionadas. El servicio usará duración por defecto.");
        // }

        $datosClienteAdicionales = [];

        $resultadoCliente = $this->clienteService->findOrCreateByTelefono($telefonoCliente, $datosClienteAdicionales);
        $cliente = $resultadoCliente['cliente'];
        $isNewRequiringData = $resultadoCliente['is_new_requiring_data'];

        if (!$cliente) {
            return ['fulfillmentText' => 'No pudimos identificarte o registrarte en el sistema. Por favor, intenta de nuevo.'];
        }

        // El modelo Cliente usa 'cliente_id' como PK
        $resultadoReserva = $this->reservaService->crearReservaEnPrimeraCanchaLibre(
            $cliente->cliente_id, // Utiliza cliente_id que es la PK del modelo Cliente
            $fechaIso,
            $horaInicioIso,
                // Asegurar que $duracionParam sea un array solo si no está vacío y es un array (Dialogflow podría enviar "" como string)
            (is_array($duracionParam) && !empty($duracionParam)) ? $duracionParam : (is_string($duracionParam) && $duracionParam !== "" ? json_decode($duracionParam, true) : null),
            $horaFinParam,
            $cliente
        );

        $mensajeFinal = $resultadoReserva['message'];

        if ($resultadoReserva['success'] && $isNewRequiringData) {
            $mensajeFinal .= "\n\nNotamos que eres nuevo/a o no tenemos tu nombre completo. Para mejorar tu experiencia, cuando quieras puedes decir 'Menú', luego 'Mis Datos' para actualizar tu información.";
        }

        return ['fulfillmentText' => $mensajeFinal];
    }
}