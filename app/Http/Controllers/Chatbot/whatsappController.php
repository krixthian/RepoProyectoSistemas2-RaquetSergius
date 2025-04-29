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
        //RESERVAS CANCHAS WALLY
        'Consulta Disponibilidad Cancha' => \App\Http\Controllers\Chatbot\IntentHandlers\ConsultaDisponibilidadCanchaHandler::class,
        'Realizar Reserva Cancha' => \App\Http\Controllers\Chatbot\IntentHandlers\RealizarReservaCanchaHandler::class,
        'cancelar reserva' => \App\Http\Controllers\Chatbot\IntentHandlers\CancelarReservaHandler::class,
        'Consulta Reserva' => \App\Http\Controllers\Chatbot\IntentHandlers\ConsultaReservaHandler::class,
        //INSCRIPCIONES CLASES ZUMBA 
        'Consulta Horarios Zumba' => \App\Http\Controllers\Chatbot\IntentHandlers\ConsultaHorariosZumbaHandler::class,
        // 'Inscripción Clase Zumba' => InscripcionClaseZumbaHandler::class,
        // 'Eventos Zumba'           => EventosZumbaHandler::class,


        //'Información e inscripcion Torneos' => \App\Http\Controllers\Chatbot\IntentHandlers\InformacionTorneosHandler::class,

        //OTROS
        // 'Ayuda / Información General' => AyudaHandler::class,
        // 'Contacto Empleado'       => ContactoEmpleadoHandler::class,
        // 'Saludo' => \App\Chatbot\IntentHandlers\SaludoHandler::class, // Ejemplo si es complicado
        // 'Despedida' => \App\Chatbot\IntentHandlers\DespedidaHandler::class, // Ejemplo si es complicado
    ];

    /**
     * Escucha los webhooks de WhatsApp.
     */
    public function escuchar(Request $request)
    {
        // Validación del Webhook (GET request)
        if ($request->isMethod('get') && $request->has('hub_mode') && $request->has('hub_verify_token')) {
            if ($request->input('hub_mode') === 'subscribe' && $request->input('hub_verify_token') === $this->verifyToken) {
                Log::info('WhatsApp Webhook Validation Success.');
                return response($request->input('hub_challenge'), 200);
            } else {
                Log::error('WhatsApp Webhook Validation Failed.');
                return response('Forbidden', 403);
            }
        }

        // Procesamiento de Mensajes (POST request)
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
                            } elseif (is_array($dialogflowResponse) && isset($dialogflowResponse['type']) && $dialogflowResponse['type'] === 'location') {
                                // Respuesta de ubicación
                                $this->sendWhatsAppLocation(
                                    $senderPhone,
                                    $dialogflowResponse['latitude'],
                                    $dialogflowResponse['longitude'],
                                    $dialogflowResponse['name'],
                                    $dialogflowResponse['address']
                                );
                            } else {
                                // Tipo de respuesta no esperado
                                Log::error("Unexpected response type from Dialogflow processing: " . json_encode($dialogflowResponse));
                                $this->sendWhatsAppMessage($senderPhone, "Hubo un error inesperado al procesar tu solicitud.");
                            }
                        } else {
                            Log::error("No response received from Dialogflow HTTP processing for message: {$messageText}");
                            // $this->sendWhatsAppMessage($senderPhone, "Lo siento, no pude procesar tu solicitud en este momento.");
                        }
                        // *** Fin Verificación y envío ***

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
            // Define el scope necesario para Dialogflow API
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
     * @param string $message El texto enviado por el usuario.
     * @param string $senderId Un ID único para la sesión (ej. número de teléfono).
     * @return string|null El texto de respuesta de Dialogflow o null en caso de error.
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
        $sessionId = 'whatsapp-' . $senderId; // ID de sesión único
        $apiUrl = "https://dialogflow.googleapis.com/v2/projects/{$this->projectId}/agent/sessions/{$sessionId}:detectIntent";

        $requestBody = [
            'queryInput' => [
                'text' => [
                    'text' => $message,
                    'languageCode' => $this->languageCode,
                ],
            ],
            // Opcional: se puede enviar más contextos o parámetros aquí
            // 'queryParams' => [ 'contexts' => $contextos ],
            // 'queryParams' => [ 'timeZone' => 'America/La_Paz' ]
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
                if (!$queryResult) { /* ... manejo de error ... */
                }

                $detectedIntent = $queryResult['intent']['displayName'] ?? null;
                $parameters = $queryResult['parameters'] ?? [];
                $fulfillmentText = $queryResult['fulfillmentText'] ?? "Disculpa, no entendí bien."; // Fallback

                Log::info("Intent: {$detectedIntent}, Params: " . json_encode($parameters));

                // --- Lógica para invocar el Handler ---
                $responseText = $fulfillmentText; // Respuesta por defecto


                if ($detectedIntent == "ubicacion") {
                    Log::info("Handling 'ubicacion' intent. Preparing location response.");
                    $finalResponse = [
                        'type' => 'location',
                        'latitude' => self::LOCATION_LATITUDE,
                        'longitude' => self::LOCATION_LONGITUDE,
                        'name' => self::LOCATION_NAME,
                        'address' => self::LOCATION_ADDRESS
                    ];
                    return $finalResponse;
                } elseif ($detectedIntent == "comunicar recepcion") {
                    return "Claro!, puedes comunicarte con la recepción al número de telefono fijo 2418133";
                } elseif ($detectedIntent && isset($this->intentHandlerMap[$detectedIntent])) {
                    $handlerClass = $this->intentHandlerMap[$detectedIntent];
                    try {
                        // Usamos el contenedor de servicios de Laravel para instanciar
                        // Esto permite inyección de dependencias si la necesitas en tus handlers
                        $handlerInstance = app($handlerClass);

                        if ($handlerInstance instanceof IntentHandlerInterface) {
                            // Llama al método handle del handler específico
                            $responseText = $handlerInstance->handle($parameters, $senderId);
                        } else {
                            Log::error("La clase {$handlerClass} no implementa IntentHandlerInterface.");
                            $responseText = "Error interno al procesar la solicitud (Handler inválido).";
                        }
                    } catch (\Throwable $handlerError) { // Captura errores al instanciar o ejecutar el handler
                        Log::error("Error ejecutando handler {$handlerClass}: " . $handlerError->getMessage() . "\n" . $handlerError->getTraceAsString());
                        $responseText = "Ocurrió un error procesando tu solicitud sobre '{$detectedIntent}'.";
                    }
                } else {
                    // Intent no mapeado o no detectado, usamos el fulfillmentText de Dialogflow
                    Log::info("No specific handler found for intent '{$detectedIntent}'. Using Dialogflow's fulfillment text.");
                }
                // --- Fin Lógica para invocar el Handler ---

                return $responseText; // Devuelve la respuesta generada por el handler o el fallback

            } else {
                // Error en la llamada a la API de Dialogflow
                Log::error("Error calling Dialogflow REST API. Status: " . $response->status() . " Body: " . $response->body());
                // Intenta dar un mensaje útil si es un error conocido
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
     * Envía un mensaje de texto a través de la API de WhatsApp Cloud.
     * @param string $recipient El número de teléfono del destinatario.
     * @param string $message El texto del mensaje a enviar.
     * @return bool True si el mensaje se envió (o al menos se intentó), False en caso de error.
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
     * Envía un mensaje de ubicación a través de la API de WhatsApp Cloud.
     * @param string $recipient El número de teléfono del destinatario.
     * @param float $latitude Latitud.
     * @param float $longitude Longitud.
     * @param string $name Nombre del lugar (opcional).
     * @param string $address Dirección del lugar (opcional).
     * @return bool True si el mensaje se envió (o al menos se intentó), False en caso de error.
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
            // Añadir nombre y dirección solo si tienen valor
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
            Log::debug('WhatsApp API Response Body: ' . $response->body()); // Debug para no llenar logs

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
     * Maneja la verificación inicial del webhook por parte de Meta/WhatsApp.
     * La ruta get para la verificación del webhook.
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