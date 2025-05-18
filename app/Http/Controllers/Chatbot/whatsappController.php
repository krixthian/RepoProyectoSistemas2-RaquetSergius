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

        // MAPEO MENU
        'Chatbot_Menu_Principal' => \App\Http\Controllers\Chatbot\IntentHandlers\MenuPrincipalHandler::class,
        'Chatbot_Menu_Mis_Datos' => \App\Http\Controllers\Chatbot\IntentHandlers\MenuMisDatosHandler::class,
        'Chatbot_Menu_Info_Club' => \App\Http\Controllers\Chatbot\IntentHandlers\MenuInfoClubHandler::class, // Handler para submenú de info
        'Chatbot_Menu_Direccion' => \App\Http\Controllers\Chatbot\IntentHandlers\MenuDireccionHandler::class,
        'Chatbot_Menu_Sobre_Nosotros' => \App\Http\Controllers\Chatbot\IntentHandlers\MenuSobreNosotrosHandler::class,
        'Chatbot_Menu_Contacto_Directo' => \App\Http\Controllers\Chatbot\IntentHandlers\MenuContactoDirectoHandler::class,

        // Intents para el flujo de actualizar datos (despues)
        'Chatbot_MisDatos_SolicitarNombre' => \App\Http\Controllers\Chatbot\IntentHandlers\MisDatosSolicitarNombreHandler::class,
        'Chatbot_MisDatos_CapturarNombre' => \App\Http\Controllers\Chatbot\IntentHandlers\MisDatosCapturarNombreHandler::class,
        'Chatbot_MisDatos_SolicitarEmail' => \App\Http\Controllers\Chatbot\IntentHandlers\MisDatosSolicitarEmailHandler::class,
        'Chatbot_MisDatos_CapturarEmail' => \App\Http\Controllers\Chatbot\IntentHandlers\MisDatosCapturarEmailHandler::class,

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

                    $messageType = $messageData['type'] ?? null;
                    $messageText = null;
                    $senderPhone = $messageData['from'];
                    $messageId = $messageData['id'];

                    if ($messageType === 'text') {
                        $messageText = $messageData['text']['body'];
                        Log::info("Received TEXT message from {$senderPhone}: {$messageText}");

                    } elseif ($messageType === 'interactive') {
                        if (isset($messageData['interactive']['type'])) {
                            $interactiveType = $messageData['interactive']['type'];
                            if ($interactiveType === 'button_reply') {
                                $messageText = $messageData['interactive']['button_reply']['id'];
                                $buttonTitle = $messageData['interactive']['button_reply']['title'];
                                Log::info("Received INTERACTIVE BUTTON_REPLY from {$senderPhone}. ID: {$messageText}, Title: {$buttonTitle}");
                            } elseif ($interactiveType === 'list_reply') {
                                $messageText = $messageData['interactive']['list_reply']['id'];
                                $listTitle = $messageData['interactive']['list_reply']['title'];
                                Log::info("Received INTERACTIVE LIST_REPLY from {$senderPhone}. ID: {$messageText}, Title: {$listTitle}");
                            } else {
                                Log::warning("Received unknown interactive type: {$interactiveType} from {$senderPhone}");
                            }
                        } else {
                            Log::warning("Received interactive message without a subtype from {$senderPhone}");
                        }
                    } else {
                        Log::info('Ignoring unhandled message type: ' . $messageType . ' from ' . $senderPhone);
                    }

                    // Solo procesar si hemos extraído un $messageText para Dialogflow
                    if ($messageText !== null) {
                        Log::info("Processing input for Dialogflow from {$senderPhone}: {$messageText}");
                        $dialogflowResponse = $this->processDialogflowViaHttp($messageText, $senderPhone);

                        // ---- INICIO Sección de envío de respuesta ----
                        if ($dialogflowResponse) {
                            if (is_string($dialogflowResponse)) {
                                $this->sendWhatsAppMessage($senderPhone, $dialogflowResponse);
                            } elseif (is_array($dialogflowResponse) && isset($dialogflowResponse['type'])) {
                                switch ($dialogflowResponse['type']) {
                                    case 'location':
                                        $this->sendWhatsAppLocation(
                                            $senderPhone,
                                            $dialogflowResponse['latitude'] ?? -16.512638,
                                            $dialogflowResponse['longitude'] ?? -68.122094,
                                            $dialogflowResponse['name'] ?? 'Ubicación',
                                            $dialogflowResponse['address'] ?? 'Dirección no especificada'
                                        );
                                        break;
                                    case 'image':
                                        if (isset($dialogflowResponse['url'])) {
                                            $this->sendWhatsAppImage(
                                                $senderPhone,
                                                $dialogflowResponse['url'],
                                                $dialogflowResponse['caption'] ?? ''
                                            );
                                        } else {
                                            Log::error("Invalid image structure from handler (missing url): " . json_encode($dialogflowResponse));
                                            $this->sendWhatsAppMessage($senderPhone, "Hubo un error al preparar la imagen.");
                                        }
                                        break;
                                    case 'interactive_buttons':
                                        if (isset($dialogflowResponse['text']) && isset($dialogflowResponse['buttons']) && is_array($dialogflowResponse['buttons'])) {
                                            $this->sendWhatsAppInteractiveButtons(
                                                $senderPhone,
                                                $dialogflowResponse['text'],
                                                $dialogflowResponse['buttons'],
                                                $dialogflowResponse['header'] ?? null
                                            );
                                        } else {
                                            Log::error("Invalid interactive_buttons structure from handler: " . json_encode($dialogflowResponse));
                                            $this->sendWhatsAppMessage($senderPhone, "Hubo un error al preparar el menú.");
                                        }
                                        break;
                                    default:
                                        Log::error("Unexpected response type ('type' key present but not handled) from handler: " . json_encode($dialogflowResponse));
                                        $this->sendWhatsAppMessage($senderPhone, "Hubo un error inesperado procesando tu solicitud.");
                                }
                            } else {
                                Log::error("Unexpected response format from Dialogflow processing or handler (array without 'type', or not string/array): " . json_encode($dialogflowResponse));
                                $this->sendWhatsAppMessage($senderPhone, "Hubo un error interno al procesar tu solicitud (formato inesperado).");
                            }
                        } else {
                            Log::error("No response (null) received from Dialogflow HTTP processing for message: {$messageText}");

                        }
                        // ---- FIN Sección de envío de respuesta ----
                    } else {
                        Log::info('Message from ' . $senderPhone . ' resulted in no actionable text for Dialogflow (e.g., unsupported interactive type or media message).');
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

                if ($detectedIntent && isset($this->intentHandlerMap[$detectedIntent])) {
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
     * Envía un mensaje interactivo con botones.
     * @param string $recipient El número de teléfono del destinatario.
     * @param string $text El texto del cuerpo del mensaje.
     * @param array $buttons Array de botones, cada botón es ['id' => 'payload_id', 'title' => 'Texto Botón']. Max 3.
     * @param string|null $headerText Texto opcional para el encabezado.
     * @return bool
     */
    private function sendWhatsAppInteractiveButtons(string $recipient, string $text, array $buttons, ?string $headerText = null): bool
    {
        if (empty($this->wsToken) || empty($this->whatsappBusinessId)) {
            Log::error('WhatsApp credentials (token or business ID) not configured.');
            return false;
        }
        if (count($buttons) > 3) {
            Log::warning('Attempted to send more than 3 buttons. WhatsApp allows a maximum of 3.');
            // Truncate to 3 or handle error as preferred
            $buttons = array_slice($buttons, 0, 3);
        }

        $formattedButtons = [];
        foreach ($buttons as $button) {
            if (isset($button['id']) && isset($button['title'])) {
                $formattedButtons[] = [
                    'type' => 'reply',
                    'reply' => [
                        'id' => $button['id'],
                        'title' => $button['title']
                    ]
                ];
            }
        }

        if (empty($formattedButtons)) {
            Log::error('No valid buttons provided for interactive message.');
            return false;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $recipient,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => [
                    'text' => $text
                ],
                'action' => [
                    'buttons' => $formattedButtons
                ]
            ]
        ];

        if ($headerText) {
            $payload['interactive']['header'] = [
                'type' => 'text',
                'text' => $headerText
            ];
        }

        try {
            $url = "https://graph.facebook.com/v22.0/{$this->whatsappBusinessId}/messages";
            Log::info("Sending WhatsApp INTERACTIVE BUTTONS to {$recipient}: " . json_encode($payload));
            $response = Http::withToken($this->wsToken)->post($url, $payload);

            Log::info('WhatsApp API Response Status (Interactive Buttons): ' . $response->status());
            Log::debug('WhatsApp API Response Body (Interactive Buttons): ' . $response->body());

            if (!$response->successful()) {
                Log::error('Error sending WhatsApp INTERACTIVE BUTTONS message: ' . $response->body());
                return false;
            }
            return true;
        } catch (Exception $e) {
            Log::error('Exception sending WhatsApp INTERACTIVE BUTTONS message: ' . $e->getMessage());
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