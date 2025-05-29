<?php // app/Http/Controllers/Chatbot/IntentHandlers/MisDatosCapturarNombreHandler.php
namespace App\Http\Controllers\Chatbot\IntentHandlers;
use App\Chatbot\IntentHandlerInterface;
use App\Services\ClienteService; // Asumiendo que usas este servicio
use Illuminate\Support\Facades\Log; //

class MisDatosCapturarNombreHandler implements IntentHandlerInterface
{
    protected ClienteService $clienteService; //

    public function __construct(ClienteService $clienteService) //
    {
        $this->clienteService = $clienteService; //
    }

    public function handle(array $parameters, string $senderId, ?string $action = null): array
    {
        Log::debug("[MisDatosCapturarNombreHandler] Teléfono Normalizado: {$senderId}"); //
        Log::debug("[MisDatosCapturarNombreHandler] Parámetros recibidos: ", $parameters); //

        // El nombre puede venir de un parámetro 'person.name' si usas @sys.person
        // o un parámetro personalizado si tienes una entidad para nombres, o 'any'.
        $nombreCapturado = $parameters['nombre_completo'] ?? $parameters['person']['name'] ?? $parameters['any'] ?? null; //

        if (is_array($nombreCapturado) && isset($nombreCapturado['name'])) { // Común para @sys.person
            $nombreCapturado = $nombreCapturado['name'];
        }

        Log::debug("[MisDatosCapturarNombreHandler] Nombre capturado (raw): '{$nombreCapturado}'"); //
        $fulfillmentText = "";
        $outputContextsToSetActive = [];
        $projectId = trim(config('dialogflow.project_id'), '/');
        $sessionId = 'whatsapp-' . $senderId;

        if ($nombreCapturado && is_string($nombreCapturado)) {
            $nombreLimpio = trim($nombreCapturado); //
            Log::debug("[MisDatosCapturarNombreHandler] Nombre limpio (después de trim): '{$nombreLimpio}'"); //

            if (strlen($nombreLimpio) > 2 && !is_numeric($nombreLimpio) && !preg_match('/(\d+\s*(hora|minuto|h|min|pm|am))/i', $nombreLimpio) && count(explode(' ', $nombreLimpio)) >= 2) { // Validación simple: al menos 3 letras, no solo números, no frases de tiempo, al menos dos palabras
                Log::info("[MisDatosCapturarNombreHandler] Nombre válido recibido: '{$nombreLimpio}'. Actualizando cliente..."); //
                $actualizado = $this->clienteService->actualizarDatosCliente($senderId, ['nombre' => $nombreLimpio]); //
                if ($actualizado) {
                    $fulfillmentText = "¡Perfecto! Tu nombre ha sido actualizado a: *{$nombreLimpio}*.\n\npuedes volver a escribir menu para volver al menú principal o escribir 'mis datos' para ver tus datos actuales.";
                    // Reactivar contexto para opciones de Mis Datos o limpiar y permitir menú principal
                    if ($projectId)
                        $outputContextsToSetActive[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/mis_datos_esperando_opcion", 'lifespanCount' => 2];
                } else {
                    $fulfillmentText = "Lo siento, no pude actualizar tu nombre en este momento. Intenta de nuevo.";
                    if ($projectId)
                        $outputContextsToSetActive[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/mis_datos_esperando_nombre_captura", 'lifespanCount' => 1]; // Mantener para reintentar
                }
            } else {
                $fulfillmentText = "El nombre '{$nombreLimpio}' no parece válido. Por favor, ingresa tu nombre y apellido. Ejemplo: Juan Pérez";
                if ($projectId)
                    $outputContextsToSetActive[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/mis_datos_esperando_nombre_captura", 'lifespanCount' => 2]; // Para reintentar
            }
        } else {
            $fulfillmentText = "No pude entender el nombre que proporcionaste. ¿Podrías intentarlo de nuevo?";
            if ($projectId)
                $outputContextsToSetActive[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/mis_datos_esperando_nombre_captura", 'lifespanCount' => 2]; // Para reintentar
        }

        if ($projectId)
            $outputContextsToSetActive[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/mis_datos_esperando_nombre_captura", 'lifespanCount' => 0]; // Siempre limpiar el contexto de captura

        return [
            'messages_to_send' => [
                [
                    'fulfillmentText' => $fulfillmentText,
                    'message_type' => 'text', // O botones para volver a Mis Datos / Menú Principal
                    'payload' => []
                    // Ejemplo de payload con botones:
                    // 'payload' => ['buttons' => [['id' => 'Mis datos', 'title' => '‹ Mis Datos'], ['id' => 'menu', 'title' => '‹ Menú Principal']]]
                ]
            ],
            'outputContextsToSetActive' => $outputContextsToSetActive
        ];
    }
}