<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface; //
use App\Services\ClienteService;
use Illuminate\Support\Facades\Log;

class MenuMisDatosHandler implements IntentHandlerInterface
{
    protected ClienteService $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    // El senderId ya viene normalizado desde whatsappController
    public function handle(array $parameters, string $senderId, ?string $action = null): array
    {
        Log::info("[MenuMisDatosHandler] Executing for senderId: {$senderId}, Action: " . ($action ?? 'N/A'));
        $cliente = $this->clienteService->findClienteByTelefono($senderId);

        $messagesToSend = [];
        $outputContextsToSetActive = [];

        if (!$cliente) {
            $messagesToSend[] = [
                'fulfillmentText' => "Parece que aún no estás registrado. Puedes intentar una acción como reservar o inscribirte para registrarte.",
                'message_type' => 'text',
                'payload' => [],
            ];
        } else {
            $nombreCliente = $cliente->nombre ?? 'No registrado';
            $emailCliente = $cliente->email ?? 'No registrado';
            $puntosCliente = $cliente->puntos ?? 'No registrados';

            $responseText = "Aquí están tus datos actuales:\n";
            $responseText .= "👤 Nombre: " . $nombreCliente . "\n";
            $responseText .= "📧 Email: " . $emailCliente . "\n";
            $responseText .= "📧 Puntos: " . $puntosCliente . "\n";
            // Primer mensaje: los datos del cliente
            $messagesToSend[] = [
                'fulfillmentText' => $responseText,
                'message_type' => 'text',
                'payload' => [],
            ];

            // Segundo mensaje: los botones de acción
            $textoBotones = "\n¿Deseas modificar alguno de estos datos?";
            $buttons = [
                ['id' => 'Modificar mi nombre', 'title' => '✏️ Modificar Nombre'], // Activa Chatbot_MisDatos_SolicitarNombre
                ['id' => 'Modificar mi email', 'title' => '📧 Modificar Email'],   // Activa Chatbot_MisDatos_SolicitarEmail
                ['id' => 'menu', 'title' => '‹ Volver al Menú']
            ];

            $messagesToSend[] = [
                'fulfillmentText' => $textoBotones, // El cuerpo del mensaje de botones
                'message_type' => 'interactive_buttons',
                'payload' => ['buttons' => $buttons],
            ];

            // Establecer un contexto para saber que el usuario está en el submenú de "Mis Datos"
            // y espera una acción relacionada con la modificación.
            $projectId = trim(config('dialogflow.project_id'), '/');
            $sessionId = 'whatsapp-' . $senderId;
            if ($projectId) {
                $outputContextsToSetActive[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/mis_datos_esperando_opcion", 'lifespanCount' => 2];
                // Limpiar contextos de otros flujos si es necesario
                $contextsToClear = ['reserva_cancha_en_progreso']; // Ejemplo
                foreach ($contextsToClear as $ctxName) {
                    $outputContextsToSetActive[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/{$ctxName}", 'lifespanCount' => 0];
                }
            }
        }

        return [
            'messages_to_send' => $messagesToSend,
            'outputContextsToSetActive' => []
        ];
    }
}