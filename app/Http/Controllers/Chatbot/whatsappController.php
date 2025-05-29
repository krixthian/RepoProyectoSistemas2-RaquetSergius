<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use Google\Cloud\Dialogflow\V2\Value;

//imports de los handlers
use App\Chatbot\IntentHandlerInterface;
// QUITA ESTOS SI YA NO LOS USAS DIRECTAMENTE O SI EL ORQUESTADOR LOS REEMPLAZA
// use App\Chatbot\IntentHandlers\ConsultaDisponibilidadCanchaHandler;
// use App\Chatbot\IntentHandlers\InformacionTorneosHandler;
use Illuminate\Support\Facades\Cache; // AÑADIR CACHE

// IMPORTA EL NUEVO ORQUESTADOR
use App\Http\Controllers\Chatbot\IntentHandlers\ReservaCanchaOrquestadorHandler;

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

        // Todos estos intents de Dialogflow apuntarán al Orquestador
        'ReservaCancha_Iniciar' => ReservaCanchaOrquestadorHandler::class,
        'ReservaCancha_Proporcionar_Fecha' => ReservaCanchaOrquestadorHandler::class,
        'ReservaCancha_Proporcionar_HoraInicio' => ReservaCanchaOrquestadorHandler::class,
        'ReservaCancha_Proporcionar_Duracion' => ReservaCanchaOrquestadorHandler::class,
        'ReservaCancha_Proporcionar_HoraFin' => ReservaCanchaOrquestadorHandler::class,
        'ReservaCancha_Proporcionar_NombreCliente' => ReservaCanchaOrquestadorHandler::class, // O tu intent global de nombre
        'ReservaCancha_Confirmar_Si' => ReservaCanchaOrquestadorHandler::class,
        'ReservaCancha_Confirmar_No' => ReservaCanchaOrquestadorHandler::class,
        'ReservaCancha_Cancelar' => ReservaCanchaOrquestadorHandler::class,
        'ReservaCancha_QuiereModificar_Fecha' => ReservaCanchaOrquestadorHandler::class,
        'ReservaCancha_QuiereModificar_HoraInicio' => ReservaCanchaOrquestadorHandler::class,
        'ReservaCancha_QuiereModificar_DuracionOFin' => ReservaCanchaOrquestadorHandler::class,

        //UBMENÚS
        'Menu_Submenu_Wally' => \App\Http\Controllers\Chatbot\IntentHandlers\MenuWallyHandler::class, // Debes crear este handler
        'Menu_Submenu_Zumba' => \App\Http\Controllers\Chatbot\IntentHandlers\MenuZumbaHandler::class, // Debes crear este handler
        'Menu_Submenu_InfoGeneral' => \App\Http\Controllers\Chatbot\IntentHandlers\MenuInfoGeneralHandler::class, // Debes crear este handler
        'Menu_Submenu_MisDatos' => \App\Http\Controllers\Chatbot\IntentHandlers\MenuMisDatosHandler::class, // Este ya existe, pero lo usaremos como submenú


        'Consulta Disponibilidad Cancha' => \App\Http\Controllers\Chatbot\IntentHandlers\ConsultaDisponibilidadCanchaHandler::class,
        // 'Realizar Reserva Cancha' => \App\Http\Controllers\Chatbot\IntentHandlers\RealizarReservaCanchaHandler::class, // ANTIGUO, reemplazado por el orquestador
        // 'ReservaCancha_Iniciar_Completo' => \App\Http\Controllers\Chatbot\IntentHandlers\RealizarReservaCanchaHandler::class, // ANTIGUO

        //cancelar reserva
        'CancelarReserva_Iniciar' => \App\Http\Controllers\Chatbot\IntentHandlers\CancelarReservaHandler::class,
        'CancelarReserva_Confirmar_Si' => \App\Http\Controllers\Chatbot\IntentHandlers\CancelarReservaHandler::class,
        'CancelarReserva_Confirmar_No' => \App\Http\Controllers\Chatbot\IntentHandlers\CancelarReservaHandler::class,

        'Saludo' => \App\Http\Controllers\Chatbot\IntentHandlers\SaludoHandler::class,
        'Default Fallback Intent' => \App\Http\Controllers\Chatbot\IntentHandlers\DefaultFallbackIntentHandler::class,

        'Consulta Reserva' => \App\Http\Controllers\Chatbot\IntentHandlers\ConsultaReservaHandler::class,

        // MAPEO ZUMBA
        'Consulta Horarios Zumba' => \App\Http\Controllers\Chatbot\IntentHandlers\ConsultaHorariosZumbaHandler::class,
        // consultar próxima clase Zumba
        'Zumba_Consultar_Mis_Clases' => \App\Http\Controllers\Chatbot\IntentHandlers\ConsultarProximaClaseZumbaHandler::class,
        //cancelar inscripción Zumba
        'Zumba_Cancelacion_Confirmar_No' => \App\Http\Controllers\Chatbot\IntentHandlers\CancelarInscripcionZumbaHandler::class,
        'Zumba_Cancelacion_Confirmar_Si' => \App\Http\Controllers\Chatbot\IntentHandlers\CancelarInscripcionZumbaHandler::class,
        'Zumba_Cancelacion_Iniciar' => \App\Http\Controllers\Chatbot\IntentHandlers\CancelarInscripcionZumbaHandler::class,
        'Zumba_Cancelacion_SeleccionarClases' => \App\Http\Controllers\Chatbot\IntentHandlers\CancelarInscripcionZumbaHandler::class,

        // Inscripción Zumba

        'Zumba_Inscripcion_Iniciar' => \App\Http\Controllers\Chatbot\IntentHandlers\InscribirClaseZumbaHandler::class,
        'Zumba_Inscripcion_ProporcionarFecha' => \App\Http\Controllers\Chatbot\IntentHandlers\InscribirClaseZumbaHandler::class,
        'Zumba_Inscripcion_SeleccionarClases' => \App\Http\Controllers\Chatbot\IntentHandlers\InscribirClaseZumbaHandler::class,

        // MAPEO MENU
        'Chatbot_Menu_Principal' => \App\Http\Controllers\Chatbot\IntentHandlers\MenuPrincipalHandler::class,
        'Chatbot_Menu_Mis_Datos' => \App\Http\Controllers\Chatbot\IntentHandlers\MenuMisDatosHandler::class,
        'Chatbot_Menu_Info_Club' => \App\Http\Controllers\Chatbot\IntentHandlers\MenuInfoClubHandler::class,
        'Chatbot_Menu_Direccion' => \App\Http\Controllers\Chatbot\IntentHandlers\MenuDireccionHandler::class,
        'Chatbot_Menu_Sobre_Nosotros' => \App\Http\Controllers\Chatbot\IntentHandlers\MenuSobreNosotrosHandler::class,
        'Chatbot_Menu_Contacto_Directo' => \App\Http\Controllers\Chatbot\IntentHandlers\MenuContactoDirectoHandler::class,

        // Intents para el flujo de actualizar datos
        'Chatbot_MisDatos_SolicitarNombre' => \App\Http\Controllers\Chatbot\IntentHandlers\MisDatosSolicitarNombreHandler::class,
        'Chatbot_MisDatos_CapturarNombre' => \App\Http\Controllers\Chatbot\IntentHandlers\MisDatosCapturarNombreHandler::class,
        'Chatbot_MisDatos_SolicitarEmail' => \App\Http\Controllers\Chatbot\IntentHandlers\MisDatosSolicitarEmailHandler::class,
        'Chatbot_MisDatos_CapturarEmail' => \App\Http\Controllers\Chatbot\IntentHandlers\MisDatosCapturarEmailHandler::class,



    ];


    private function normalizePhoneNumber(string $phoneNumber): string //
    {
        if (strpos($phoneNumber, 'whatsapp:+') === 0) {
            //quita el mas // Este comentario parece incorrecto, quita 'whatsapp:'
            return substr($phoneNumber, strlen('whatsapp:')); //
        }
        return preg_replace('/[^0-9+]/', '', $phoneNumber); //
    }

    /**
     * Escucha los webhooks de WhatsApp.
     */
    public function escuchar(Request $request) //
    {
        if ($request->isMethod('get') && $request->has('hub_mode') && $request->has('hub_verify_token')) { //
            if ($request->input('hub_mode') === 'subscribe' && $request->input('hub_verify_token') === $this->verifyToken) { //
                Log::info('WhatsApp Webhook Validation Success.'); //
                return response($request->input('hub_challenge'), 200); //
            } else {
                Log::error('WhatsApp Webhook Validation Failed.'); //
                return response('Forbidden', 403); //
            }
        }

        // Procesamiento de 'POST'
        if ($request->isMethod('post')) {
            $data = $request->json()->all();
            Log::info('Incoming WhatsApp Data: ' . json_encode($data));

            if (isset($data['object']) && $data['object'] === 'whatsapp_business_account') {
                if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
                    $messageData = $data['entry'][0]['changes'][0]['value']['messages'][0];
                    $senderPhoneInput = $messageData['from']; // Para enviar respuesta a WhatsApp
                    $normalizedSenderId = $this->normalizePhoneNumber($senderPhoneInput); // Para lógica interna y Dialogflow sessionId
                    $messageText = null;
                    $userProfileName = $data['entry'][0]['changes'][0]['value']['contacts'][0]['profile']['name'] ?? null; // Obtener nombre de perfil


                    if (($messageData['type'] ?? null) === 'text') {
                        $messageText = $messageData['text']['body'];
                    } elseif (($messageData['type'] ?? null) === 'interactive') {
                        if (isset($messageData['interactive']['button_reply']['id'])) {
                            $messageText = $messageData['interactive']['button_reply']['id'];
                        } elseif (isset($messageData['interactive']['list_reply']['id'])) {
                            $messageText = $messageData['interactive']['list_reply']['id'];
                        }
                    }

                    if ($messageText !== null) {
                        Log::info("Processing input for Dialogflow from {$normalizedSenderId} (original: {$senderPhoneInput}): {$messageText}");

                        $handlerResponse = $this->processDialogflowAndHandleIntent($messageText, $normalizedSenderId, $userProfileName);

                        $messagesSent = false;
                        // -------- INICIO SECCIÓN CRÍTICA PARA CONTEXTOS --------
                        if ($handlerResponse) { // Asegurarse que $handlerResponse no es null
                            // Guardar contextos para el PRÓXIMO turno de Dialogflow
                            if (isset($handlerResponse['outputContextsToSetActive'])) {
                                $cacheKeyContexts = 'df_contexts_for_next_request_' . $normalizedSenderId;
                                if (!empty($handlerResponse['outputContextsToSetActive'])) {
                                    Cache::put($cacheKeyContexts, $handlerResponse['outputContextsToSetActive'], now()->addMinutes(15));
                                    Log::info("[WhatsappController] Contextos para PRÓXIMO turno de DF guardados en caché {$cacheKeyContexts}: ", $handlerResponse['outputContextsToSetActive']);
                                } else {
                                    Cache::forget($cacheKeyContexts);
                                    Log::info("[WhatsappController] Limpiando contextos de caché (array vacío) para {$cacheKeyContexts}.");
                                }
                            } else {
                                // Si el handler no devuelve 'outputContextsToSetActive', es importante limpiar los viejos para no arrastrarlos.
                                Cache::forget('df_contexts_for_next_request_' . $normalizedSenderId);
                                Log::info("[WhatsappController] Limpiando contextos de caché (clave no definida) para df_contexts_for_next_request_{$normalizedSenderId}.");
                            }
                            // -------- FIN SECCIÓN CRÍTICA PARA CONTEXTOS --------
                            if ($handlerResponse && isset($handlerResponse['messages_to_send']) && is_array($handlerResponse['messages_to_send'])) {
                                foreach ($handlerResponse['messages_to_send'] as $messageToSend) {
                                    if (isset($messageToSend['fulfillmentText'])) {
                                        $textForThisMessage = $messageToSend['fulfillmentText'];
                                        $messageType = $messageToSend['message_type'] ?? 'text';
                                        $payload = $messageToSend['payload'] ?? [];

                                        Log::info("[WhatsappController] Enviando mensaje a {$senderPhoneInput}. Tipo: {$messageType}. Texto: {$textForThisMessage}");

                                        if ($messageType === 'text') {
                                            $this->sendWhatsAppMessage($senderPhoneInput, $textForThisMessage);
                                        } elseif ($messageType === 'interactive_buttons' && isset($payload['buttons'])) {
                                            $this->sendWhatsAppInteractiveButtons($senderPhoneInput, $textForThisMessage, $payload['buttons'], $payload['header'] ?? null);
                                        } elseif ($messageType === 'image' && isset($payload['image_url'])) {
                                            $captionParaImagen = $payload['caption'] ?? $textForThisMessage;
                                            $this->sendWhatsAppImage($senderPhoneInput, $payload['image_url'], $captionParaImagen);
                                        } elseif ($messageType === 'location' && isset($payload['latitude']) && isset($payload['longitude'])) {
                                            $this->sendWhatsAppLocation($senderPhoneInput, $payload['latitude'], $payload['longitude'], $payload['name'] ?? '', $payload['address'] ?? '');
                                        }
                                        // Añadir más tipos de mensajes si los tienes (listas, etc.)
                                        $messagesSent = true;
                                    }
                                }

                                // Guardar contextos para el PRÓXIMO turno de Dialogflow DESPUÉS de enviar todos los mensajes
                                if (isset($handlerResponse['outputContextsToSetActive'])) {
                                    $cacheKeyContexts = 'df_contexts_for_next_request_' . $normalizedSenderId;
                                    if (!empty($handlerResponse['outputContextsToSetActive'])) {
                                        Cache::put($cacheKeyContexts, $handlerResponse['outputContextsToSetActive'], now()->addMinutes(15));
                                        Log::info("[WhatsappController] Contextos para el PRÓXIMO turno de DF guardados en caché {$cacheKeyContexts}: ", $handlerResponse['outputContextsToSetActive']);
                                    } else {
                                        Cache::forget($cacheKeyContexts); // Limpiar si el array de contextos está vacío
                                        Log::info("[WhatsappController] Limpiando contextos de caché para {$cacheKeyContexts} porque outputContextsToSetActive estaba vacío.");
                                    }
                                } else {
                                    Cache::forget('df_contexts_for_next_request_' . $normalizedSenderId);
                                }


                                if ($messagesSent) {
                                    return response()->json(['status' => 'messages_processed_and_sent'], 200);
                                } else {
                                    Log::error("[WhatsappController] El handler devolvió una estructura 'messages_to_send' pero estaba vacía o malformada para {$normalizedSenderId}.");
                                    return response()->json(['status' => 'handler_response_empty_messages'], 200);
                                }
                            }

                        } else { // Fallback si el handler no devuelve el formato esperado
                            Log::error("[WhatsappController] No se generó respuesta del handler con 'messages_to_send' o formato incorrecto para {$normalizedSenderId}. Respuesta del handler: ", (array) $handlerResponse);
                            // Enviar un mensaje de error genérico al usuario si es necesario
                            $this->sendWhatsAppMessage($senderPhoneInput, "Lo siento, no pude procesar tu última solicitud en este momento.");
                            return response()->json(['status' => 'handler_error_or_no_response_structure'], 200);
                        }
                    }
                }
            }
            return response()->json(['status' => 'ok_no_message_processed'], 200);
        }
        return response()->json(['status' => 'ignored_method'], 200);
    }

    private function processDialogflowAndHandleIntent(string $message, string $normalizedSenderId, ?string $userProfileName): ?array // Ahora puede devolver array o string
    {
        if (empty($this->projectId)) {
            Log::error("Dialogflow Project ID not set.");
            return ['fulfillmentText' => "Error de configuración del asistente.", 'message_type' => 'text'];
        }
        $accessToken = $this->getGoogleAccessToken();
        if (!$accessToken) {
            return ['fulfillmentText' => "Error interno de autenticación con el asistente.", 'message_type' => 'text'];
        }

        $dialogflowSessionId = 'whatsapp-' . $normalizedSenderId;
        $apiUrl = "https://dialogflow.googleapis.com/v2/projects/{$this->projectId}/agent/sessions/{$dialogflowSessionId}:detectIntent";

        $requestBody = [
            'queryInput' => ['text' => ['text' => $message, 'languageCode' => $this->languageCode]],
        ];

        // -------- INICIO SECCIÓN CRÍTICA PARA CONTEXTOS --------
        $cacheKeyContexts = 'df_contexts_for_next_request_' . $normalizedSenderId;
        $contextsParaEnviarADialogflow = Cache::get($cacheKeyContexts);
        if (!empty($contextsParaEnviarADialogflow)) {
            // Asegúrate de que $contextsParaEnviarADialogflow sea un array de objetos de contexto válidos
            // El formato que genera ReservaCanchaOrquestadorHandler::generarNombresContextosActivos ya debería ser correcto.
            $requestBody['queryParams'] = ['contexts' => $contextsParaEnviarADialogflow];
            Log::info("[PDI] Enviando con contextos de caché a Dialogflow {$cacheKeyContexts}: ", $contextsParaEnviarADialogflow);
            Cache::forget($cacheKeyContexts); // Limpiar después de usar
        }
        // -------- FIN SECCIÓN CRÍTICA PARA CONTEXTOS --------

        try {
            // Antes de Http::post
            $cacheKeyContexts = 'df_contexts_for_next_request_' . $normalizedSenderId;
            $contextsParaEnviarADialogflow = Cache::get($cacheKeyContexts);
            if (!empty($contextsParaEnviarADialogflow)) {
                $requestBody['queryParams'] = ['contexts' => $contextsParaEnviarADialogflow]; // Asegúrate que este formato sea el que espera la API v2
                Log::info("[PDI] Enviando con contextos de caché a Dialogflow {$cacheKeyContexts}: ", $contextsParaEnviarADialogflow);
                Cache::forget($cacheKeyContexts);
            }

            Log::info("Calling Dialogflow REST API: {$apiUrl} for session: {$dialogflowSessionId} with body: " . json_encode($requestBody));
            $response = Http::withToken($accessToken)->timeout(30)->post($apiUrl, $requestBody);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info("Dialogflow REST API Response: " . json_encode($responseData));

                $queryResult = $responseData['queryResult'] ?? null;
                if (!$queryResult) {
                    Log::error("[processDialogflowAndHandleIntent] QueryResult no encontrado.");
                    return ['fulfillmentText' => "Error al procesar la respuesta de Dialogflow.", 'message_type' => 'text'];
                }

                $detectedIntentName = $queryResult['intent']['displayName'] ?? 'Default Fallback Intent';
                $parameters = $queryResult['parameters'] ?? [];
                $action = $queryResult['action'] ?? '';
                $fulfillmentTextFromDialogflow = $queryResult['fulfillmentText'] ?? "Disculpa, no entendí bien.";
                // $outputContextsFromDialogflow = $queryResult['outputContexts'] ?? []; // Estos son los que DF genera por sí mismo.

                if ($userProfileName) {
                    $parameters['user_profile_name'] = $userProfileName;
                }
                Log::info("Intent: {$detectedIntentName}, Action: '{$action}', Params: " . json_encode($parameters));

                $handlerResponse = ['fulfillmentText' => $fulfillmentTextFromDialogflow, 'message_type' => 'text'];

                if (isset($this->intentHandlerMap[$detectedIntentName])) {
                    $handlerClass = $this->intentHandlerMap[$detectedIntentName];
                    try {
                        $handlerInstance = app($handlerClass);

                        if ($handlerInstance instanceof IntentHandlerInterface) {
                            $handlerResponse = $handlerInstance->handle($parameters, $normalizedSenderId, $action);
                            if (!is_array($handlerResponse) || !isset($handlerResponse['messages_to_send'])) {
                                Log::warning("[PDI] Handler {$handlerClass} no devolvió 'messages_to_send'. Creando fallback. Respuesta original: ", (array) $handlerResponse);
                                $fallbackText = null;
                                if (is_string($handlerResponse)) {
                                    $fallbackText = $handlerResponse;
                                } elseif (is_array($handlerResponse) && isset($handlerResponse['fulfillmentText'])) {
                                    // Si el handler devolvió el formato antiguo que SÓLO tenía fulfillmentText y quizas outputContextsToSetActive
                                    $fallbackText = $handlerResponse['fulfillmentText'];
                                } else {
                                    // Si no, usa el fulfillmentText directo de Dialogflow (para Default Fallback Intent, etc.)
                                    $fallbackText = $fulfillmentTextFromDialogflow ?: "No pude procesar eso.";
                                }

                                $contextsFromDialogflowItself = $queryResult['outputContexts'] ?? [];

                                $handlerResponse = [
                                    'messages_to_send' => [['fulfillmentText' => $fallbackText, 'message_type' => 'text', 'payload' => []]],
                                    // Para un fallback, usualmente o no envías contextos o envías los que Dialogflow mismo generó.
                                    // No los que el orquestador quería para el *siguiente* paso específico, porque ese paso falló.
                                    'outputContextsToSetActive' => $contextsFromDialogflowItself
                                ];
                            }
                            return $handlerResponse;

                            if (is_string($handlerResponse)) { // Compatibilidad
                                $handlerResponse = ['fulfillmentText' => $handlerResponse, 'message_type' => 'text'];
                            }

                            // Si el handler quiere que se guarden contextos para la *próxima* llamada a Dialogflow
                            // if(isset($handlerResponse['outputContextsToCache'])){
                            //    Cache::put('df_output_contexts_for_next_request_' . $normalizedSenderId, $handlerResponse['outputContextsToCache'], now()->addMinutes(self::CACHE_TTL_MINUTES));
                            //    unset($handlerResponse['outputContextsToCache']); // No enviar esto a WhatsApp
                            // }

                        } else {
                            Log::error("La clase {$handlerClass} no implementa IntentHandlerInterface.");
                        }
                    } catch (\Throwable $handlerError) {
                        Log::error("Error ejecutando handler {$handlerClass}: " . $handlerError->getMessage() . "\n" . $handlerError->getTraceAsString());
                        $handlerResponse['fulfillmentText'] = "Ocurrió un error procesando tu solicitud sobre '{$detectedIntentName}'.";
                    }
                } else {
                    Log::info("No specific handler found for intent '{$detectedIntentName}'.");
                }
                return $handlerResponse; // Devolvemos la respuesta del handler

            } else {
                Log::error("Error calling Dialogflow REST API. Status: " . $response->status() . " Body: " . $response->body());
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? 'Error desconocido';
                return ['fulfillmentText' => "Hubo un problema contactando al asistente (API: {$response->status()} - {$errorMessage}).", 'message_type' => 'text'];
            }

        } catch (Exception $e) {
            Log::error("Exception calling Dialogflow REST API: " . $e->getMessage());
            return ['fulfillmentText' => "Hubo una excepción conectando con el asistente.", 'message_type' => 'text'];
        }
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


    private function processDialogflowViaHttp(string $message, string $normalizedSenderId): ?array
    {
        if (empty($this->projectId)) {
            Log::error("Dialogflow Project ID not set.");
            return ['fulfillmentText' => "Error de configuración del asistente."];
        }
        $accessToken = $this->getGoogleAccessToken();
        if (!$accessToken) {
            return ['fulfillmentText' => "Error interno de autenticación con el asistente."];
        }

        $dialogflowSessionId = 'whatsapp-' . $normalizedSenderId; // Consistente
        $apiUrl = "https://dialogflow.googleapis.com/v2/projects/{$this->projectId}/agent/sessions/{$dialogflowSessionId}:detectIntent";
        $requestBody = [
            'queryInput' => ['text' => ['text' => $message, 'languageCode' => $this->languageCode]],
            // 'queryParams' => ['contexts' => []] // Si necesitas enviar contextos *a* Dialogflow desde aquí
        ];

        try {
            Log::info("Calling Dialogflow REST API: {$apiUrl} for session: {$dialogflowSessionId}");
            $response = Http::withToken($accessToken)->timeout(30)->post($apiUrl, $requestBody);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info("Dialogflow REST API Response: " . json_encode($responseData));

                $queryResult = $responseData['queryResult'] ?? null;
                if (!$queryResult) {
                    Log::error("[processDialogflowViaHttp] QueryResult no encontrado.");
                    return ['fulfillmentText' => "Error al procesar la respuesta de Dialogflow."];
                }

                $detectedIntentName = $queryResult['intent']['displayName'] ?? 'Default Fallback Intent';
                // LOS PARÁMETROS YA VIENEN COMO ARRAY DESDE $response->json()
                $parameters = $queryResult['parameters'] ?? [];
                $action = $queryResult['action'] ?? ''; // OBTENER LA ACCIÓN
                $fulfillmentTextFromDialogflow = $queryResult['fulfillmentText'] ?? "Disculpa, no entendí bien.";
                // $outputContextsFromDialogflow = $queryResult['outputContexts'] ?? []; // Contextos que Dialogflow generó

                Log::info("Intent: {$detectedIntentName}, Action: '{$action}', Params: " . json_encode($parameters));

                // Respuesta por defecto será la de Dialogflow, por si no hay handler o el handler no la modifica.
                $handlerResponsePayload = ['fulfillmentText' => $fulfillmentTextFromDialogflow];
                // Si Dialogflow generó contextos, los incluimos por defecto, el handler puede sobrescribirlos.
                if (!empty($queryResult['outputContexts'])) {
                    $handlerResponsePayload['outputContexts'] = $queryResult['outputContexts'];
                }


                if (isset($this->intentHandlerMap[$detectedIntentName])) {
                    $handlerClass = $this->intentHandlerMap[$detectedIntentName];
                    try {
                        $handlerInstance = app($handlerClass);

                        if ($handlerInstance instanceof IntentHandlerInterface) {
                            // NOW, ALL HANDLERS (VIA THE INTERFACE) ACCEPT THE ACTION PARAMETER
                            // THE ORCHESTRATOR WILL USE IT, OTHERS CAN IGNORE IT.
                            $handlerResponsePayload = $handlerInstance->handle($parameters, $normalizedSenderId, $action);

                            // Critical: Ensure $handlerResponsePayload has the correct structure for Dialogflow.
                            // If $handlerInstance->handle returned a simple string (from older handlers),
                            // you need to wrap it. The Orquestador should already return the correct array.
                            if (is_string($handlerResponsePayload)) {
                                $fulfillmentTextFromHandler = $handlerResponsePayload;
                                $handlerResponsePayload = ['fulfillmentText' => $fulfillmentTextFromHandler];
                                // If Dialogflow provided outputContexts and this simple handler didn't override them,
                                // you might want to re-add them here.
                                if (!empty($queryResult['outputContexts'])) {
                                    $handlerResponsePayload['outputContexts'] = $queryResult['outputContexts'];
                                }
                            } elseif (!is_array($handlerResponsePayload) || !isset($handlerResponsePayload['fulfillmentText'])) {
                                Log::error("Handler {$handlerClass} para '{$detectedIntentName}' devolvió formato inesperado. Usando fulfillment de Dialogflow.", $handlerResponsePayload);
                                $handlerResponsePayload = ['fulfillmentText' => $fulfillmentTextFromDialogflow];
                                if (!empty($queryResult['outputContexts'])) {
                                    $handlerResponsePayload['outputContexts'] = $queryResult['outputContexts'];
                                }
                            }
                            // The $handlerResponsePayload should now be a valid array for the webhook response.

                        } else {
                            Log::error("La clase {$handlerClass} para '{$detectedIntentName}' no implementa IntentHandlerInterface.");
                            // $handlerResponsePayload was already defaulted to Dialogflow's fulfillment
                        }
                    } catch (\Throwable $handlerError) {
                        Log::error("Error ejecutando handler {$handlerClass} para '{$detectedIntentName}': " . $handlerError->getMessage() . "\n" . $handlerError->getTraceAsString());
                        $handlerResponsePayload['fulfillmentText'] = "Ocurrió un error procesando tu solicitud sobre '{$detectedIntentName}'.";
                    }
                } else {
                    Log::info("No specific handler found for intent '{$detectedIntentName}'. Using Dialogflow's fulfillment text.");
                    // $handlerResponsePayload was already defaulted to Dialogflow's fulfillment
                }

                return $handlerResponsePayload;

            } else {
                Log::error("Error calling Dialogflow REST API. Status: " . $response->status() . " Body: " . $response->body());
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? 'Error desconocido';
                return ['fulfillmentText' => "Hubo un problema contactando al asistente (API: {$response->status()} - {$errorMessage})."];
            }

        } catch (Exception $e) {
            Log::error("Exception calling Dialogflow REST API: " . $e->getMessage());
            return ['fulfillmentText' => "Hubo una excepción conectando con el asistente."];
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

    /**
     * Convierte un valor de campo de Dialogflow (Google\Protobuf\Value) a un tipo PHP nativo.
     * Necesitarás importar Google\Cloud\Dialogflow\V2\Value en los uses.
     */

}