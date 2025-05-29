<?php
namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ClienteService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator; // Para validar email

class MisDatosCapturarEmailHandler implements IntentHandlerInterface
{
    protected ClienteService $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    public function handle(array $parameters, string $senderId, ?string $action = null): array
    {
        Log::info("[MisDatosCapturarEmailHandler] Executing for senderId: {$senderId}, Params: ", $parameters);

        $emailCapturado = $parameters['email'] ?? $parameters['any'] ?? null; // @sys.email debería estar en el parámetro 'email'

        $fulfillmentText = "";
        $outputContextsToSetActive = [];
        $projectId = trim(config('dialogflow.project_id'), '/');
        $sessionId = 'whatsapp-' . $senderId;

        if ($emailCapturado && is_string($emailCapturado)) {
            $emailLimpio = trim($emailCapturado);

            $validator = Validator::make(['email' => $emailLimpio], ['email' => 'required|email']);

            if (!$validator->fails()) {
                $actualizado = $this->clienteService->actualizarDatosCliente($senderId, ['email' => $emailLimpio]);
                if ($actualizado) {
                    $fulfillmentText = "¡Excelente! Tu email ha sido actualizado a: *{$emailLimpio}*.\n\nPuedes volver al menú de 'Mis Datos' o al menú principal.";
                    if ($projectId)
                        $outputContextsToSetActive[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/mis_datos_esperando_opcion", 'lifespanCount' => 2];
                } else {
                    $fulfillmentText = "Lo siento, no pude actualizar tu email en este momento. Intenta de nuevo.";
                    if ($projectId)
                        $outputContextsToSetActive[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/mis_datos_esperando_email_captura", 'lifespanCount' => 1];
                }
            } else {
                $fulfillmentText = "El correo electrónico '{$emailLimpio}' no parece válido. Por favor, inténtalo de nuevo o escribe 'no' si prefieres omitir.";
                if ($projectId)
                    $outputContextsToSetActive[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/mis_datos_esperando_email_captura", 'lifespanCount' => 2];
            }
        } else {
            $fulfillmentText = "No pude entender el email que proporcionaste. ¿Podrías intentarlo de nuevo?";
            if ($projectId)
                $outputContextsToSetActive[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/mis_datos_esperando_email_captura", 'lifespanCount' => 2];
        }

        if ($projectId)
            $outputContextsToSetActive[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/mis_datos_esperando_email_captura", 'lifespanCount' => 0];

        $payload = [
            'buttons' => [
                ['id' => 'Mis datos', 'title' => '‹ Mis Datos'],
                ['id' => 'menu', 'title' => '‹ Menú Principal']
            ]
        ];

        return [
            'messages_to_send' => [
                [
                    'fulfillmentText' => $fulfillmentText,
                    'message_type' => 'interactive_buttons',
                    'payload' => $payload
                ]
            ],
            'outputContextsToSetActive' => $outputContextsToSetActive
        ];
    }
}