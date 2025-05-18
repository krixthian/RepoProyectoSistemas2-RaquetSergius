<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

// Imports para la autenticación de Google
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;

//imports de los handlers
use App\Chatbot\IntentHandlerInterface;
use App\Chatbot\IntentHandlers\ConsultaDisponibilidadCanchaHandler;
use App\Chatbot\IntentHandlers\InformacionTorneosHandler;

class whatsappController extends Controller
{
    protected $projectId;
    protected $languageCode = 'es';
    protected $googleCredentialsPath;
    protected $wsToken;
    protected $whatsappBusinessId;
    protected $verifyToken;

    // DATOS UBICACION ---
    private const LOCATION_LATITUDE = -16.498637;
    private const LOCATION_LONGITUDE = -68.132286;
    private const LOCATION_NAME = 'Ubicación de Raquet Sergius';
    private const LOCATION_ADDRESS = 'Nos encontramos en la calle Ascarrunz!';
    // -----------------------------------------------------------

    public function __construct()
    {
        $this->projectId = env('DIALOGFLOW_PROJECT_ID');
        $this->googleCredentialsPath = env('GOOGLE_APPLICATION_CREDENTIALS');

        $this->wsToken = env('WHATSAPP_TOKEN');
        $this->whatsappBusinessId = env('WHATSAPP_BUSINESS_ID');
        $this->verifyToken = env('WHATSAPP_VERIFY_TOKEN', 'andres');

        // Verificar configuraciones esenciales
        if (empty($this->projectId)) {
            Log::error('DIALOGFLOW_PROJECT_ID no está configurado en .env');
        }
        if (empty($this->googleCredentialsPath) || !file_exists($this->googleCredentialsPath)) {
            Log::error('GOOGLE_APPLICATION_CREDENTIALS no está configurado o el archivo no existe en la ruta: ' . $this->googleCredentialsPath);
        }
        if (empty($this->wsToken) || empty($this->whatsappBusinessId)) {
            Log::error('WhatsApp credentials (token or business ID) not configured in .env');
        }
    }
    /**
     * Mapeo de Nombres de Intent de Dialogflow a Clases Handler.
     * los nombres de los intents deben coincidir con los definidos en Dialogflow.
     */
    private $intentHandlerMap = [
        //MAPEO RESERVAS
        'Consulta Disponibilidad Cancha' => \App\Http\Controllers\Chatbot\IntentHandlers\ConsultaDisponibilidadCanchaHandler::class,
        'Realizar Reserva Cancha' => \App\Http\Controllers\Chatbot\IntentHandlers\RealizarReservaCanchaHandler::class,
        'cancelar reserva' => \App\Http\Controllers\Chatbot\IntentHandlers\CancelarReservaHandler::class,
        'Consulta Reserva' => \App\Http\Controllers\Chatbot\IntentHandlers\ConsultaReservaHandler::class,

        //MAPEO ZUMBA
        'Consulta Horarios Zumba' => \App\Http\Controllers\Chatbot\IntentHandlers\ConsultaHorariosZumbaHandler::class,
        'Inscribir Clase Zumba' => \App\Http\Controllers\Chatbot\IntentHandlers\InscribirClaseZumbaHandler::class,
        // ...
    ];

    /**
     * Escucha los webhooks de WhatsApp.
     */
    public function escuchar(Request $request)
    {
        // whatsapp webhook get
        if ($request->isMethod('get') && $request->has('hub_mode') && $request->has('hub_verify_token')) {
            if ($request->input('hub_mode') === 'subscribe' && $request->input('hub_verify_token') === $this->verifyToken) {
                Log::info('WhatsApp Webhook Validation Success.');
                return response($request->input('hub_challenge'), 200);
            } else {
                Log::error('WhatsApp Webhook Validation Failed.');
                return response('Forbidden', 403);
            }
        }

        // procesar mensajes post
        if ($request->isMethod('post')) {
            $data = $request->json()->all();
            Log::info('Incoming WhatsApp Data: ' . json_encode($data));

            if (isset($data['object']) && $data['object'] === 'whatsapp_business_account') {
                if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
                    $messageData = $data['entry'][0]['changes'][0]['value']['messages'][0];

                    if (isset($messageData['type']) && $messageData['type'] === 'text') {
                        $messageText = $messageData['text']['body'];
                        $senderPhone = $messageData['from'];
                        $messageId = $messageData['id'];

                        Log::info("Message from {$senderPhone}: {$messageText}");

                        // *** Llama a Dialogflow usando HTTP directa ***
                        $dialogflowResponse = $this->processDialogflowViaHttp($messageText, $senderPhone);

                        // *** Verificar tipo de respuesta y enviar mensaje ***
                        if ($dialogflowResponse) {
                            if (is_string($dialogflowResponse)) {
                                // Respuesta de texto normal
                                $this->sendWhatsAppMessage($senderPhone, $dialogflowResponse);
                            } elseif (is_array($dialogflowResponse) && isset($dialogflowResponse['type'])) {
                                switch ($dialogflowResponse['type']) {
                                    case 'location':
                                        // Respuesta de ubicación
                                        $this->sendWhatsAppLocation(
                                            $senderPhone,
                                            $dialogflowResponse['latitude'] ?? 0,
                                            $dialogflowResponse['longitude'] ?? 0,
                                            $dialogflowResponse['name'] ?? '',
                                            $dialogflowResponse['address'] ?? ''
                                        );
                                        break;
                                    case 'image':
                                        // Respuesta de imagen
                                        if (isset($dialogflowResponse['url']) && isset($dialogflowResponse['caption'])) {
                                            $this->sendWhatsAppImage(
                                                $senderPhone,
                                                $dialogflowResponse['url'],
                                                $dialogflowResponse['caption']
                                            );
                                        } else {
                                            Log::error("Invalid image structure from handler: " . json_encode($dialogflowResponse));
                                            $this->sendWhatsAppMessage($senderPhone, "Hubo un error al preparar la imagen de horarios.");
                                        }
                                        break;
                                    default:
                                        // Tipo de respuesta no esperado
                                        Log::error("Unexpected response type from handler: " . json_encode($dialogflowResponse));
                                        $this->sendWhatsAppMessage($senderPhone, "Hubo un error inesperado al procesar tu solicitud.");
                                }
                            } else {
                                // Tipo de respuesta no string ni array esperado
                                Log::error("Unexpected response format from Dialogflow processing: " . gettype($dialogflowResponse));
                                $this->sendWhatsAppMessage($senderPhone, "Hubo un error interno al procesar tu solicitud.");
                            }
                        } else {
                            Log::error("No response received from Dialogflow HTTP processing for message: {$messageText}");
                        }

                    } else {
                        Log::info('Ignoring non-text message type: ' . ($messageData['type'] ?? 'unknown'));
                    }
                }
            }
            return response()->json(['status' => 'ok'], 200);
        }

        Log::info('Received non-message POST or other method request.');
        return response()->json(['status' => 'ignored'], 200);
    }


    /**
     * Obtiene un token de acceso de Google usando las credenciales de servicio.
     * @return string|null El token de acceso o null si falla.
     */
    private function getGoogleAccessToken(): ?string
    {
        if (empty($this->googleCredentialsPath) || !file_exists($this->googleCredentialsPath)) {
            Log::error("Cannot get access token: Credentials path invalid or file missing.");
            return null;
        }

        try {
            $scopes = ['https://www.googleapis.com/auth/cloud-platform', 'https://www.googleapis.com/auth/dialogflow'];

            $credentials = new ServiceAccountCredentials($scopes, $this->googleCredentialsPath);
            $handler = HttpHandlerFactory::build();
            $authToken = $credentials->fetchAuthToken($handler);

            if (isset($authToken['access_token'])) {
                Log::info("Google Access Token obtained successfully.");
                return $authToken['access_token'];
            } else {
                Log::error("Failed to obtain Google Access Token. Response: " . json_encode($authToken));
                return null;
            }
        } catch (Exception $e) {
            Log::error("Exception obtaining Google Access Token: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Procesa el mensaje llamando a la API REST de Dialogflow Detect Intent.
     *
     * @param string $message
     * @param string $senderId
     * @return string|null
     */
    private function processDialogflowViaHttp(string $message, string $senderId): string|array|null
    {
        if (empty($this->projectId)) {
            Log::error("Dialogflow Project ID not set. Cannot process message.");
            return "Error de configuración del asistente.";
        }

        // 1. Obtener Token de Acceso
        $accessToken = $this->getGoogleAccessToken();
        if (!$accessToken) {
            return "Error interno de autenticación con el asistente.";
        }

        // 2. Preparar la Llamada a la API
        $sessionId = 'whatsapp-' . $senderId;
        $apiUrl = "https://dialogflow.googleapis.com/v2/projects/{$this->projectId}/agent/sessions/{$sessionId}:detectIntent";

        $requestBody = [
            'queryInput' => [
                'text' => [
                    'text' => $message,
                    'languageCode' => $this->languageCode,
                ],
            ],
        ];

        // 3. Realizar la Llamada HTTP
        try {
            Log::info("Calling Dialogflow REST API: {$apiUrl}");
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->post($apiUrl, $requestBody);

            // 4. Procesar la Respuesta
            if ($response->successful()) {
                $responseData = $response->json();
                Log::info("Dialogflow REST API Response: " . json_encode($responseData));

                $queryResult = $responseData['queryResult'] ?? null;
                if (!$queryResult) {
                }

                $detectedIntent = $queryResult['intent']['displayName'] ?? null;
                $parameters = $queryResult['parameters'] ?? [];
                $fulfillmentText = $queryResult['fulfillmentText'] ?? "Disculpa, no entendí bien.";

                Log::info("Intent: {$detectedIntent}, Params: " . json_encode($parameters));

                // --- Lógica para invocar el Handler ---
                $finalResponse = null;

                if ($detectedIntent == "ubicacion") {
                    Log::info("Handling 'ubicacion' intent. Preparing location response.");
                    $finalResponse = [
                        'type' => 'location',
                        'latitude' => self::LOCATION_LATITUDE,
                        'longitude' => self::LOCATION_LONGITUDE,
                        'name' => self::LOCATION_NAME,
                        'address' => self::LOCATION_ADDRESS
                    ];
                } elseif ($detectedIntent == "comunicar recepcion") {
                    Log::info("Handling 'comunicar recepcion' intent.");
                    $finalResponse = "Claro!, puedes comunicarte con la recepción al número de telefono fijo 2418133";
                } elseif ($detectedIntent && isset($this->intentHandlerMap[$detectedIntent])) {
                    $handlerClass = $this->intentHandlerMap[$detectedIntent];
                    try {
                        $handlerInstance = app($handlerClass);

                        if ($handlerInstance instanceof IntentHandlerInterface) {
                            $handlerOutput = $handlerInstance->handle($parameters, $senderId);

                            if (is_string($handlerOutput)) {
                                $finalResponse = $handlerOutput;
                            } elseif (is_array($handlerOutput)) {
                                if (isset($handlerOutput['type'])) {
                                    $finalResponse = $handlerOutput;
                                } elseif (isset($handlerOutput['fulfillmentText']) && is_string($handlerOutput['fulfillmentText'])) {
                                    $finalResponse = $handlerOutput['fulfillmentText'];
                                } else {
                                    Log::error("Handler {$handlerClass} returned an array in an unexpected format: " . json_encode($handlerOutput));
                                    $finalResponse = "Hubo un error al procesar tu solicitud (respuesta de handler inesperada).";
                                }
                            } else {
                                Log::error("Handler {$handlerClass} returned an unexpected data type: " . gettype($handlerOutput));
                                $finalResponse = "Hubo un error al procesar tu solicitud (tipo de handler inesperado).";
                            }
                        } else {
                            Log::error("La clase {$handlerClass} no implementa IntentHandlerInterface.");
                            $finalResponse = "Error interno al procesar la solicitud (Handler inválido).";
                        }
                    } catch (\Throwable $handlerError) {
                        Log::error("Error ejecutando handler {$handlerClass}: " . $handlerError->getMessage() . "\n" . $handlerError->getTraceAsString());
                        $finalResponse = "Ocurrió un error procesando tu solicitud sobre '{$detectedIntent}'.";
                    }
                } else {
                    // Intent no mapeado o no detectado
                    Log::info("No specific handler found for intent '{$detectedIntent}'. Using Dialogflow's fulfillment text.");
                    $finalResponse = $fulfillmentText;
                }
                // --- Fin Lógica para invocar el Handler ---

                // Si $finalResponse sigue siendo null aquí (no debería pasar si hay un fulfillmentText de Dialogflow)
                if (is_null($finalResponse)) {
                    Log::warning("Final response is null after intent processing for intent: {$detectedIntent}. Falling back to generic error.");
                    return "Lo siento, no pude procesar tu solicitud en este momento.";
                }

                return $finalResponse; // Devuelve la respuesta procesada

            } else {

                Log::error("Error calling Dialogflow REST API. Status: " . $response->status() . " Body: " . $response->body());

                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? 'Error desconocido';
                return "Hubo un problema contactando al asistente (Error API: " . $response->status() . " - " . $errorMessage . ").";
            }

        } catch (Exception $e) {
            // Error durante la conexión o procesamiento HTTP
            Log::error("Exception calling Dialogflow REST API: " . $e->getMessage());
            return "Hubo una excepción conectando con el asistente.";
        }
    }

    /**
     * enviar un mensaje de texto
     * @param string $recipient
     * @param string $message
     * @return bool
     */
    private function sendWhatsAppMessage(string $recipient, string $message): bool
    {
        if (empty($this->wsToken) || empty($this->whatsappBusinessId)) {
            Log::error('WhatsApp credentials (token or business ID) not configured.');
            return false;
        }
        try {
            $url = "https://graph.facebook.com/v22.0/{$this->whatsappBusinessId}/messages";
            Log::info("Sending WhatsApp message to {$recipient}: {$message}");
            $response = Http::withToken($this->wsToken)->post($url, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $recipient,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message
                ]
            ]);
            Log::info('WhatsApp API Response Status: ' . $response->status());
            Log::info('WhatsApp API Response Body: ' . $response->body());
            if (!$response->successful()) {
                Log::error('Error sending WhatsApp message: ' . $response->body());
                return false;
            }
            return true;
        } catch (Exception $e) {
            Log::error('Exception sending WhatsApp message: ' . $e->getMessage());
            return false;
        }
    }


    /**
     * @param string $recipient
     * @param float $latitude
     * @param float $longitude
     * @param string $name
     * @param string $address
     * @return bool
     */
    private function sendWhatsAppLocation(string $recipient, float $latitude, float $longitude, string $name = '', string $address = ''): bool
    {
        if (empty($this->wsToken) || empty($this->whatsappBusinessId)) {
            Log::error('WhatsApp credentials (token or business ID) not configured.');
            return false;
        }
        try {
            $url = "https://graph.facebook.com/v22.0/{$this->whatsappBusinessId}/messages";
            $locationData = [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];

            if (!empty($name)) {
                $locationData['name'] = $name;
            }
            if (!empty($address)) {
                $locationData['address'] = $address;
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $recipient,
                'type' => 'location',
                'location' => $locationData,
            ];

            Log::info("Sending WhatsApp LOCATION message to {$recipient}: " . json_encode($locationData));
            $response = Http::withToken($this->wsToken)->post($url, $payload);

            Log::info('WhatsApp API Response Status: ' . $response->status());
            Log::debug('WhatsApp API Response Body: ' . $response->body());

            if (!$response->successful()) {
                Log::error('Error sending WhatsApp LOCATION message: ' . $response->body());
                return false;
            }
            return true;
        } catch (Exception $e) {
            Log::error('Exception sending WhatsApp LOCATION message: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @param string $recipient
     * @param string $imageUrl
     * @param string $caption
     * @return bool
     */
    private function sendWhatsAppImage(string $recipient, string $imageUrl, string $caption = ''): bool
    {
        if (empty($this->wsToken) || empty($this->whatsappBusinessId)) {
            Log::error('WhatsApp credentials (token or business ID) not configured for sending image.');
            return false;
        }
        if (filter_var($imageUrl, FILTER_VALIDATE_URL) === FALSE) {
            Log::error("Invalid image URL provided: {$imageUrl}");
            return false;
        }

        try {
            $url = "https://graph.facebook.com/v22.0/{$this->whatsappBusinessId}/messages";

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $recipient,
                'type' => 'image',
                'image' => [
                    // Usa 'link' para URL directa
                    'link' => $imageUrl,
                    // 'caption' => $caption
                ]
            ];
            if (!empty($caption)) {
                $payload['image']['caption'] = $caption;
            }


            Log::info("Sending WhatsApp IMAGE to {$recipient}. URL: {$imageUrl}, Caption: " . ($caption ?: 'None'));
            $response = Http::withToken($this->wsToken)->post($url, $payload);

            Log::info('WhatsApp API Response Status (Image): ' . $response->status());
            Log::debug('WhatsApp API Response Body (Image): ' . $response->body());

            if (!$response->successful()) {
                Log::error('Error sending WhatsApp IMAGE message: ' . $response->body());

                return false;
            }
            return true;
        } catch (Exception $e) {
            Log::error('Exception sending WhatsApp IMAGE message: ' . $e->getMessage());
            return false;
        }
    }


    /**
     * verificacion get del webhook de whatsapp
     */
    public function verifyToken(Request $request)
    {
        Log::info('Attempting webhook verification via verifyToken method.');
        if ($request->input('hub_mode') === 'subscribe' && $request->input('hub_verify_token') === $this->verifyToken) {
            Log::info('Webhook verification successful.');
            return response($request->input('hub_challenge'), 200);
        } else {
            Log::warning('Webhook verification failed.');
            return response('Forbidden', 403);
        }
    }
}