<?php // MisDatosCapturarEmailHandler.php
namespace App\Http\Controllers\Chatbot\IntentHandlers;
use App\Chatbot\IntentHandlerInterface;
use App\Services\ClienteService;
use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Cache;

class MisDatosCapturarEmailHandler implements IntentHandlerInterface
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
        // Quitar cualquier otro carácter que no sea un dígito (o el + si decides mantenerlo para estándares internacionales)
        // Para este caso, asumimos que solo queremos los dígitos para que coincida con el formato en BD.
        return preg_replace('/[^0-9]/', '', $phoneNumber);
    }

    public function handle(array $parameters, string $senderId): array
    {
        $telefonoNormalizado = $this->normalizePhoneNumber($senderId);
        // $userState = Cache::get('user_state:'.$senderId);
        // if ($userState !== 'awaiting_misdatos_email') {
        //    return ['fulfillmentText' => "Disculpa, no esperaba esa respuesta ahora."];
        // }

        $emailCapturado = strtolower(trim($parameters['email_capturado'] ?? ''));

        if ($emailCapturado === 'no' || $emailCapturado === 'cancelar') {
            $this->clienteService->actualizarDatosCliente($telefonoNormalizado, ['email' => null]);
            // Cache::forget('user_state:'.$senderId);
            return ['fulfillmentText' => "Entendido. No se guardará tu correo electrónico. Tus datos han sido actualizados."];
        }

        if (!filter_var($emailCapturado, FILTER_VALIDATE_EMAIL)) {
            // Cache::put('user_state:'.$senderId, 'awaiting_misdatos_email', 300); // Mantener estado
            return ['fulfillmentText' => "El correo electrónico no parece válido. Por favor, inténtalo de nuevo o escribe 'no' para omitir."];
        }

        $this->clienteService->actualizarDatosCliente($telefonoNormalizado, ['email' => $emailCapturado]);
        // Cache::forget('user_state:'.$senderId);
        return ['fulfillmentText' => "¡Gracias! Tu correo electrónico ha sido guardado. Tus datos están actualizados."];
    }
}