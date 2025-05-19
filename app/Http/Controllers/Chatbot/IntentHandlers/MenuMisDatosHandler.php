<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ClienteService;
use Illuminate\Support\Facades\Log;

class MenuMisDatosHandler implements IntentHandlerInterface
{
    protected ClienteService $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        if (strpos($phoneNumber, 'whatsapp:+') === 0) {
            return substr($phoneNumber, strlen('whatsapp:+'));
        }
        return preg_replace('/[^0-9+]/', '', $phoneNumber);
    }

    public function handle(array $parameters, string $senderId): array
    {
        $telefonoNormalizado = $this->normalizePhoneNumber($senderId);
        $cliente = $this->clienteService->findClienteByTelefono($telefonoNormalizado);

        if (!$cliente) {
            // Esto podría pasar si el cliente envió "menu" pero no está en la BD aún.
            // Podríamos intentar crearlo aquí o guiarlo.
            // Por ahora, un mensaje simple.
            return ['fulfillmentText' => "Parece que aún no estás registrado. Puedes intentar una acción como reservar o inscribirte para registrarte."];
        }

        $responseText = "Aquí están tus datos:\n";
        $responseText .= "📞 Teléfono: " . ($cliente->telefono ?? 'No registrado') . "\n";
        $responseText .= "👤 Nombre: " . ($cliente->nombre ?? 'No registrado') . "\n";
        $responseText .= "📧 Email: " . ($cliente->email ?? 'No registrado');

        // Preguntar si quiere actualizar si falta el nombre
        if (empty($cliente->nombre)) {
            return [
                'type' => 'interactive_buttons',
                'text' => $responseText . "\n\nNotamos que no tenemos tu nombre. ¿Deseas agregarlo?",
                'buttons' => [
                    ['id' => 'misdatos_solicitar_nombre_si', 'title' => 'Sí, agregar nombre'],
                    ['id' => 'misdatos_solicitar_nombre_no', 'title' => 'No, gracias']
                ]
            ];
        } else {
            // Si ya tiene nombre, preguntar si quiere actualizar nombre o email
            return [
                'type' => 'interactive_buttons',
                'text' => $responseText . "\n\n¿Deseas modificar tu nombre o email?",
                'buttons' => [
                    ['id' => 'misdatos_solicitar_nombre_si', 'title' => 'Modificar Nombre'],
                    ['id' => 'misdatos_solicitar_email_si', 'title' => 'Modificar Email'],
                    ['id' => 'menu', 'title' => 'Volver al Menú']
                ]
            ];
        }
    }
}