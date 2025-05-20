<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ClienteService;
use Illuminate\Support\Facades\Log;

class MisDatosCapturarNombreHandler implements IntentHandlerInterface
{
    protected ClienteService $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        // Quitar el prefijo 'whatsapp:+' si está presente
        if (strpos($phoneNumber, 'whatsapp:+') === 0) {
            $phoneNumber = substr($phoneNumber, strlen('whatsapp:+'));
        }
        // Quitar cualquier otro carácter que no sea un dígito
        return preg_replace('/[^0-9]/', '', $phoneNumber);
    }

    public function handle(array $parameters, string $senderId): array
    {
        $telefonoNormalizado = $this->normalizePhoneNumber($senderId);

        // Obtener el nombre del parámetro 'any' como se ve en tus logs.
        $nombreCapturado = $parameters['any'] ?? null;

        // Log detallado para depuración
        Log::debug("[MisDatosCapturarNombreHandler] Teléfono Normalizado: {$telefonoNormalizado}");
        Log::debug("[MisDatosCapturarNombreHandler] Parámetros recibidos: " . json_encode($parameters));
        Log::debug("[MisDatosCapturarNombreHandler] Nombre capturado (raw desde parameters['any']): " . ($nombreCapturado === null ? 'NULL' : "'{$nombreCapturado}'"));

        $nombreLimpio = is_string($nombreCapturado) ? trim($nombreCapturado) : '';
        $longitudNombreLimpio = strlen($nombreLimpio);

        Log::debug("[MisDatosCapturarNombreHandler] Nombre limpio (después de trim): '{$nombreLimpio}'");
        Log::debug("[MisDatosCapturarNombreHandler] Longitud del nombre limpio: {$longitudNombreLimpio}");

        // Condición de validación
        if (empty($nombreLimpio) || $longitudNombreLimpio < 3) {
            Log::warning("[MisDatosCapturarNombreHandler] Nombre inválido o muy corto. Nombre limpio: '{$nombreLimpio}', Longitud: {$longitudNombreLimpio}. Se volverá a preguntar.");
            return ['fulfillmentText' => "No entendí bien tu nombre. Por favor, escribe tu nombre completo e inténtalo de nuevo:"];
        }

        Log::info("[MisDatosCapturarNombreHandler] Nombre válido recibido: '{$nombreLimpio}'. Actualizando cliente...");
        $clienteActualizado = $this->clienteService->actualizarDatosCliente($telefonoNormalizado, ['nombre' => $nombreLimpio]);

        if (!$clienteActualizado) {
            Log::error("[MisDatosCapturarNombreHandler] Error al actualizar el nombre para el cliente con teléfono: {$telefonoNormalizado}");
            return ['fulfillmentText' => "Hubo un problema al guardar tu nombre. Por favor, intenta más tarde."];
        }

        Log::info("[MisDatosCapturarNombreHandler] Nombre actualizado para cliente {$telefonoNormalizado} a: {$nombreLimpio}");

        // Flujo para solicitar el email
        return [
            'type' => 'interactive_buttons',
            'text' => "¡Gracias, " . htmlspecialchars($nombreLimpio) . "! Tu nombre ha sido guardado. ¿Deseas agregar o actualizar tu correo electrónico? (Es opcional)",
            'buttons' => [
                ['id' => 'misdatos_solicitar_email_si', 'title' => 'Sí, agregar email'],
                ['id' => 'misdatos_solicitar_email_no', 'title' => 'No, gracias']
            ]
        ];
    }
}