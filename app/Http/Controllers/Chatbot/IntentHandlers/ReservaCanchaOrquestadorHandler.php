<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ClienteService;
use App\Services\ReservaService;
use App\Http\Controllers\Chatbot\IntentHandlers\ConsultaDisponibilidadCanchaHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

class ReservaCanchaOrquestadorHandler implements IntentHandlerInterface
{
    protected ClienteService $clienteService;
    protected ReservaService $reservaService;
    protected ConsultaDisponibilidadCanchaHandler $consultaDisponibilidadHandler;

    //propiedades
    private const HORA_INICIO_OPERACION = 8;
    private const HORA_FIN_OPERACION = 22;
    private const MAX_DIAS_ANTICIPACION_RESERVA = 7;
    private const MIN_DURACION_RESERVA_MINUTOS = 60;
    private const MAX_DURACION_RESERVA_MINUTOS = 180;
    private const INTERVALO_MINUTOS_RESERVA = 30; // Las reservas deben ser en intervalos de 30 min



    private const CACHE_TTL_MINUTES = 30; // Tiempo de vida de la caché en minutos
    private const CACHE_KEY_PREFIX = 'reserva_cancha_'; // Prefijo para las claves de caché

    // Estructura de datos interna para la sesión de reserva
    private array $datosReserva = [
        'current_flow' => 'reserva_cancha',
        'step' => 'inicio',
        'fecha' => null,
        'hora_inicio' => null,
        'hora_fin' => null,
        'duracion' => null,
        'nombre_cliente_temporal' => null,
        'confirmacion_pendiente' => false,
        'user_profile_name' => null,
    ];

    private string $senderId;
    private string $cacheKey;

    public function __construct(
        ClienteService $clienteService,
        ReservaService $reservaService,
        ConsultaDisponibilidadCanchaHandler $consultaDisponibilidadHandler
    ) {
        $this->clienteService = $clienteService;
        $this->reservaService = $reservaService;
        $this->consultaDisponibilidadHandler = $consultaDisponibilidadHandler;
    }

    // --- Métodos de Gestión de Caché ---
    private function loadSessionData(string $senderId): void
    {
        $this->senderId = $this->normalizePhoneNumberInternal($senderId);
        $this->cacheKey = self::CACHE_KEY_PREFIX . $this->senderId;
        $cachedData = Cache::get($this->cacheKey);

        $defaultData = [
            'current_flow' => 'reserva_cancha',
            'step' => 'inicio',
            'fecha' => null,
            'hora_inicio' => null,
            'hora_fin' => null,
            'duracion' => null,
            'nombre_cliente_temporal' => null,
            'confirmacion_pendiente' => false,
            'user_profile_name' => null,
        ];

        if ($cachedData) {
            // Asegurarse de que todas las claves existan, fusionando con los defaults
            $this->datosReserva = array_merge($defaultData, $cachedData);
        } else {
            $this->datosReserva = $defaultData;
        }
    }

    private function saveSessionData(): void
    {
        Cache::put($this->cacheKey, $this->datosReserva, now()->addMinutes(self::CACHE_TTL_MINUTES));
    }

    private function clearSessionData(): void
    {
        Cache::forget($this->cacheKey);
        // Restablecer datosReserva a su estado inicial
        $this->datosReserva = [
            'current_flow' => 'reserva_cancha',
            'step' => 'inicio',
            'fecha' => null,
            'hora_inicio' => null,
            'hora_fin' => null,
            'duracion' => null,
            'nombre_cliente_temporal' => null,
            'confirmacion_pendiente' => false,
            'user_profile_name' => null,
        ];
    }

    private function normalizePhoneNumberInternal(string $phoneNumber): string
    {
        if (strpos($phoneNumber, 'whatsapp:+') === 0) {
            return substr($phoneNumber, strlen('whatsapp:'));
        }
        return preg_replace('/[^0-9+]/', '', $phoneNumber);
    }


    public function handle(array $parameters, string $senderId, ?string $action = null): array
    {
        $this->loadSessionData($senderId);
        Log::debug("[{$this->cacheKey}] Orquestador Invocado. Action: {$action}. Step Caché: {$this->datosReserva['step']}. Params:", $parameters);
        Log::debug("[{$this->cacheKey}] Datos en caché ANTES de procesar:", $this->datosReserva);

        // Guardar user_profile_name si viene en los parámetros (del whatsappController)
        if (isset($parameters['user_profile_name'])) {
            $this->datosReserva['user_profile_name'] = $parameters['user_profile_name'];
        }

        $this->procesarParametrosEntrantes($parameters);
        Log::debug("[{$this->cacheKey}] Datos en caché DESPUÉS de fusionar params:", $this->datosReserva);

        $respuesta = $this->gestionarFlujoReserva($action, $parameters);

        $this->saveSessionData();
        Log::debug("[{$this->cacheKey}] Datos en caché GUARDADOS:", $this->datosReserva);
        Log::debug("[{$this->cacheKey}] Respuesta del Orquestador (para WhatsappController):", $respuesta);

        return $respuesta;
    }

    private function procesarParametrosEntrantes(array $parameters): void
    {
        $paramFecha = $parameters['fecha'] ?? null;
        $paramHoraInicio = $parameters['hora_inicio'] ?? $parameters['horaini'] ?? null; // 'horaini' por compatibilidad si Dialogflow lo usa
        $paramDuracion = $parameters['duracion'] ?? null;
        $paramHoraFin = $parameters['hora_fin'] ?? $parameters['horafin'] ?? null;
        $paramNombreCliente = $parameters['nombre_cliente'] ?? $parameters['person']['name'] ?? null; // Si @sys.person devuelve objeto

        $cambioSignificativo = false;

        if ($paramFecha) {
            try {
                $fechaParseada = Carbon::parse($paramFecha)->toDateString();
                if ($this->datosReserva['fecha'] !== $fechaParseada) {
                    $this->datosReserva['fecha'] = $fechaParseada;
                    // Resetear datos dependientes si la fecha cambia
                    $this->datosReserva['hora_inicio'] = null;
                    $this->datosReserva['hora_fin'] = null;
                    $this->datosReserva['duracion'] = null;
                    $this->datosReserva['confirmacion_pendiente'] = false;
                    $this->datosReserva['step'] = 'esperando_hora_inicio'; // Forzar pedir hora para la nueva fecha
                    $cambioSignificativo = true;
                }
            } catch (InvalidFormatException $e) {
                Log::warning("[{$this->cacheKey}] Fecha inválida de Dialogflow: {$paramFecha}");
            }
        }

        if ($paramHoraInicio) {
            try {
                $horaParseada = Carbon::parse($paramHoraInicio)->format('H:i:s');
                if ($this->datosReserva['hora_inicio'] !== $horaParseada) {
                    $this->datosReserva['hora_inicio'] = $horaParseada;
                    $this->datosReserva['hora_fin'] = null;
                    $this->datosReserva['duracion'] = null;
                    $this->datosReserva['confirmacion_pendiente'] = false;
                    if ($this->datosReserva['fecha']) { // Solo cambiar step si ya tenemos fecha
                        $this->datosReserva['step'] = 'esperando_duracion_o_fin';
                    }
                    $cambioSignificativo = true;
                }
            } catch (InvalidFormatException $e) {
                Log::warning("[{$this->cacheKey}] Hora de inicio inválida de Dialogflow: {$paramHoraInicio}");
            }
        }

        if ($paramDuracion) {
            $this->datosReserva['duracion'] = $paramDuracion; // Dialogflow puede enviar objeto o string
            $this->datosReserva['hora_fin'] = null; // Duración tiene prioridad
            $this->datosReserva['confirmacion_pendiente'] = false;
            if ($this->datosReserva['fecha'] && $this->datosReserva['hora_inicio']) {
                $this->datosReserva['step'] = 'listo_para_nombre_o_confirmacion';
            }
            $cambioSignificativo = true;
        } elseif ($paramHoraFin) { // Solo procesar hora_fin si no se dio duración
            try {
                $horaFinParseada = Carbon::parse($paramHoraFin)->format('H:i:s');
                if ($this->datosReserva['hora_fin'] !== $horaFinParseada) {
                    $this->datosReserva['hora_fin'] = $horaFinParseada;
                    $this->datosReserva['confirmacion_pendiente'] = false;
                    if ($this->datosReserva['fecha'] && $this->datosReserva['hora_inicio']) {
                        $this->datosReserva['step'] = 'listo_para_nombre_o_confirmacion';
                    }
                    $cambioSignificativo = true;
                }
            } catch (InvalidFormatException $e) {
                Log::warning("[{$this->cacheKey}] Hora de fin inválida de Dialogflow: {$paramHoraFin}");
            }
        }

        if ($paramNombreCliente) {
            if (is_array($paramNombreCliente) && isset($paramNombreCliente['name'])) { // Para @sys.person
                $paramNombreCliente = $paramNombreCliente['name'];
            }
            if (is_string($paramNombreCliente)) {
                $nombreLimpio = trim($paramNombreCliente);
                if (!empty($nombreLimpio) && $this->datosReserva['nombre_cliente_temporal'] !== $nombreLimpio) {
                    $this->datosReserva['nombre_cliente_temporal'] = $nombreLimpio;
                    if ($this->datosReserva['step'] === 'esperando_nombre') {
                        $this->datosReserva['step'] = 'listo_para_confirmacion';
                    }
                    $cambioSignificativo = true;
                }
            }
        }
    }

    private function gestionarFlujoReserva(?string $action, array $currentDialogflowParams): array
    {
        // --- INICIO: Validación SIMPLE de Reserva Existente al Iniciar ---
        // Esta validación se ejecuta si es la acción de iniciar o el step es 'inicio',
        // no hay fecha aún, y no hemos pasado por la advertencia de reserva existente.
        if (
            ($action === 'reservaCancha.iniciar' || $this->datosReserva['step'] === 'inicio') &&
            !$this->datosReserva['fecha'] &&
            !isset($this->datosReserva['decision_sobre_reserva_existente_tomada'])
        ) {

            $userProfileName = $this->datosReserva['user_profile_name'];
            $this->clearSessionData(); // Limpia para un nuevo flujo, pero preservamos el nombre de perfil
            $this->datosReserva['user_profile_name'] = $userProfileName;

            $cliente = $this->clienteService->findOrCreateByTelefono($this->senderId, ['nombre_perfil_whatsapp' => $this->datosReserva['user_profile_name']])['cliente'];

            if ($cliente) {
                $reservasActivas = $this->reservaService->getReservasActivasFuturasPorCliente($cliente->cliente_id);

                if ($reservasActivas && !$reservasActivas->isEmpty()) {
                    $this->datosReserva['step'] = 'finalizado_o_cancelado'; // Detener flujo de NUEVA reserva
                    $mensaje = "Hola {$cliente->nombre}! Ya tienes " . ($reservasActivas->count() > 1 ? "reservas programadas" : "una reserva programada") . ":\n";
                    foreach ($reservasActivas as $idx => $infoRes) {
                        $fechaRes = Carbon::parse($infoRes->fecha)->locale('es')->isoFormat('dddd D [de] MMMM');
                        $horaInicioRes = Carbon::parse($infoRes->hora_inicio)->format('H:i');
                        $horaFinRes = Carbon::parse($infoRes->hora_fin)->format('H:i');
                        $canchaNombre = $infoRes->cancha->nombre ?? 'N/A';
                        $mensaje .= ($idx + 1) . ". {$canchaNombre} el {$fechaRes} de {$horaInicioRes} a {$horaFinRes}.\n";
                    }
                    $mensaje .= "\nSi deseas hacer otra reserva, primero debes gestionar o cancelar las existentes.";
                    $contextos = $this->generarNombresContextosActivosParaLimpiar(['reserva_cancha_en_progreso', 'reserva_cancha_esperando_fecha']);
                    return $this->prepararRespuesta($mensaje, $contextos, 'text');
                }
            }
            $this->datosReserva['decision_sobre_reserva_existente_tomada'] = true; // Marcar que ya pasamos esta verificación
        }
        // --- FIN: Validación SIMPLE de Reserva Existente ---


        if ($action === 'reservaCancha.iniciar' && !$this->datosReserva['fecha']) {
            $this->datosReserva['step'] = 'esperando_fecha';
        }
        // --- VALIDACIÓN DE FECHA (SI EXISTE) ---
        if ($this->datosReserva['fecha']) {
            try {
                $fechaCarbon = Carbon::parse($this->datosReserva['fecha'])->startOfDay();
                $hoy = Carbon::today()->startOfDay();
                $fechaLimite = $hoy->copy()->addDays(self::MAX_DIAS_ANTICIPACION_RESERVA);

                if ($fechaCarbon->isPast() && !$fechaCarbon->isToday()) {
                    $this->datosReserva['fecha'] = null;
                    $this->datosReserva['hora_inicio'] = null; // Resetear también hora si la fecha es inválida
                    $this->datosReserva['step'] = 'esperando_fecha';
                    $contextos = $this->generarNombresContextosActivos(['reserva_cancha_esperando_fecha']);
                    return $this->prepararRespuesta("La fecha que indicaste ({$fechaCarbon->locale('es')->isoFormat('D MMM')}) ya pasó. Por favor, indica una fecha a partir de hoy.", $contextos);
                }
                if ($fechaCarbon->gt($fechaLimite)) {
                    $this->datosReserva['fecha'] = null;
                    $this->datosReserva['hora_inicio'] = null;
                    $this->datosReserva['step'] = 'esperando_fecha';
                    $contextos = $this->generarNombresContextosActivos(['reserva_cancha_esperando_fecha']);
                    $mensajeLimite = "Solo puedes reservar con un máximo de " . self::MAX_DIAS_ANTICIPACION_RESERVA . " días de anticipación (hasta el " . $fechaLimite->locale('es')->isoFormat('D [de] MMMM') . "). ¿Qué fecha eliges?";
                    return $this->prepararRespuesta($mensajeLimite, $contextos);
                }
                // Si la fecha es válida y el step era 'esperando_fecha' o 'inicio', avanzamos a 'esperando_hora_inicio'.
                // O si la acción fue 'reservaCancha.proporcionarFecha'.
                if ($this->datosReserva['step'] === 'esperando_fecha' || $this->datosReserva['step'] === 'inicio' || $action === 'reservaCancha.proporcionarFecha') {
                    $this->datosReserva['step'] = 'esperando_hora_inicio';
                }
            } catch (InvalidFormatException $e) {
                $this->datosReserva['fecha'] = null;
                $this->datosReserva['hora_inicio'] = null;
                $this->datosReserva['step'] = 'esperando_fecha';
                $contextos = $this->generarNombresContextosActivos(['reserva_cancha_esperando_fecha']);
                return $this->prepararRespuesta("No entendí la fecha proporcionada. ¿Podrías decirla de nuevo? (Ej: mañana, próximo martes)", $contextos);
            }
        }
        // --- FIN VALIDACIÓN DE FECHA ---


        // Manejar acciones explícitas que pueden interrumpir el flujo secuencial
        if ($action === 'reservaCancha.cancelar') {
            $this->clearSessionData();
            $this->datosReserva['step'] = 'finalizado_o_cancelado';
            return $this->prepararRespuesta("Entendido, he cancelado el proceso de reserva.", $this->generarNombresContextosActivosParaLimpiar(['reserva_cancha_en_progreso']), 'text');
        }
        if ($action === 'reservaCancha.confirmarSi' && $this->datosReserva['confirmacion_pendiente']) {
            return $this->intentarCrearReserva();
        }
        if ($action === 'reservaCancha.confirmarNo' && $this->datosReserva['confirmacion_pendiente']) {
            $this->datosReserva['confirmacion_pendiente'] = false;
            $this->datosReserva['step'] = 'modificando_reserva';
            $this->datosReserva['hora_fin'] = null;
            $this->datosReserva['duracion'] = null;
            $contextos = $this->generarNombresContextosActivos(['reserva_cancha_modificando']);
            return $this->prepararRespuesta("Entendido. ¿Qué deseas cambiar: la fecha, la hora de inicio, o la duración/hora de finalización?", $contextos);
        }

        if ($this->datosReserva['step'] === 'listo_para_nombre_o_confirmacion' || $this->datosReserva['step'] === 'esperando_nombre') {
            $nombreProporcionadoPorUsuario = $this->datosReserva['nombre_cliente_temporal'] ?? null;
            $nombreDePerfilWhatsapp = $this->datosReserva['user_profile_name'] ?? null;

            $datosParaClienteService = [];
            if ($nombreProporcionadoPorUsuario) {
                // Si el usuario ya dio un nombre explícitamente en este flujo, lo usamos.
                $datosParaClienteService['nombre'] = $nombreProporcionadoPorUsuario;
            } elseif ($nombreDePerfilWhatsapp) {
                // Si no, usamos el nombre de perfil de WhatsApp como sugerencia para findOrCreate
                $datosParaClienteService['nombre_perfil_whatsapp'] = $nombreDePerfilWhatsapp;
            }

            $resultadoCliente = $this->clienteService->findOrCreateByTelefono($this->senderId, $datosParaClienteService);
            $cliente = $resultadoCliente['cliente'];

            if (!$cliente) {
                $this->clearSessionData();
                $this->datosReserva['step'] = 'finalizado_o_cancelado';
                return $this->prepararRespuesta("Hubo un problema al identificarte. Por favor, intenta de nuevo más tarde.", $this->generarNombresContextosActivosParaLimpiar(['reserva_cancha_en_progreso']));
            }

            // Caso 1: El cliente es nuevo Y no tenemos un nombre válido para él (ni del perfil ni proporcionado)
            // O el cliente existe pero NO tiene nombre, Y el usuario aún no ha proporcionado uno en este flujo.
            if (($resultadoCliente['is_new_requiring_data'] || (isset($cliente->nombre) && empty($cliente->nombre))) && empty($nombreProporcionadoPorUsuario)) {
                $this->datosReserva['step'] = 'esperando_nombre';
                $mensajeNombre = "Para completar tu reserva, necesito tu nombre completo por favor:";
                if ($resultadoCliente['is_new_requiring_data']) {
                    $mensajeNombre = "Como eres un nuevo cliente, ¿podrías decirme tu nombre completo para registrarte y completar la reserva?";
                } else if (empty($cliente->nombre)) {
                    $mensajeNombre = "Veo que ya estás registrado, pero no tenemos tu nombre. ¿Podrías proporcionármelo para la reserva?";
                }
                $contextos = $this->generarNombresContextosActivos(['reserva_cancha_esperando_nombre']);
                return $this->prepararRespuesta($mensajeNombre, $contextos);
            }

            // Caso 2: El usuario proporcionó un nombre explícitamente en este flujo ('nombre_cliente_temporal')
            // Y este nombre es diferente al que ya tiene el cliente (o el cliente no tenía nombre).
            // Aquí SÍ actualizamos.
            if ($nombreProporcionadoPorUsuario && (!isset($cliente->nombre) || $nombreProporcionadoPorUsuario !== $cliente->nombre)) {
                Log::info("[{$this->cacheKey}] Actualizando nombre del cliente ID {$cliente->cliente_id} a '{$nombreProporcionadoPorUsuario}' basado en entrada del flujo.");
                $this->clienteService->actualizarDatosCliente($this->senderId, ['nombre' => $nombreProporcionadoPorUsuario]);
                $cliente->refresh(); // Actualizar el objeto cliente
            }
            $this->datosReserva['step'] = 'esperando_confirmacion';
        }


        if ($this->datosReserva['step'] === 'modificando_reserva') {
            if ($action === 'reservaCancha.quiereModificarFecha') {
                $this->datosReserva['fecha'] = null;
                $this->datosReserva['hora_inicio'] = null;
                $this->datosReserva['hora_fin'] = null;
                $this->datosReserva['duracion'] = null;
                $this->datosReserva['step'] = 'esperando_fecha';
            } elseif ($action === 'reservaCancha.quiereModificarHoraInicio') {
                $this->datosReserva['hora_inicio'] = null;
                $this->datosReserva['hora_fin'] = null;
                $this->datosReserva['duracion'] = null;
                $this->datosReserva['step'] = 'esperando_hora_inicio';
                if (!$this->datosReserva['fecha']) {
                    $this->datosReserva['step'] = 'esperando_fecha';
                }
            } elseif ($action === 'reservaCancha.quiereModificarDuracionOFin') {
                $this->datosReserva['hora_fin'] = null;
                $this->datosReserva['duracion'] = null;
                $this->datosReserva['step'] = 'esperando_duracion_o_fin';
                if (!$this->datosReserva['fecha']) {
                    $this->datosReserva['step'] = 'esperando_fecha';
                } elseif (!$this->datosReserva['hora_inicio']) {
                    $this->datosReserva['step'] = 'esperando_hora_inicio';
                }
            } else {
                $contextos = $this->generarNombresContextosActivos(['reserva_cancha_modificando']);
                return $this->prepararRespuesta("No entendí qué quieres cambiar. Puedes decir 'la fecha', 'la hora' o 'la duración'.", $contextos);
            }
        }

        // Si el step es 'inicio' y ya pasamos la validación de reserva activa (o no aplica) y no hay fecha, ponemos step a 'esperando_fecha'
        if ($this->datosReserva['step'] === 'inicio' && !$this->datosReserva['fecha']) {
            $this->datosReserva['step'] = 'esperando_fecha';
        }

        // 1. PROCESAR/PEDIR FECHA
        if ($this->datosReserva['step'] === 'esperando_fecha') {
            if (!$this->datosReserva['fecha'] && isset($currentDialogflowParams['fecha'])) { // Tomar de params si se proporcionó con la acción de iniciar o fecha
                try {
                    $this->datosReserva['fecha'] = Carbon::parse($currentDialogflowParams['fecha'])->toDateString();
                } catch (InvalidFormatException $e) { /* se manejará abajo */
                }
            }

            if (!$this->datosReserva['fecha']) {
                $contextos = $this->generarNombresContextosActivos(['reserva_cancha_esperando_fecha']);
                return $this->prepararRespuesta("Para tu reserva, ¿para qué fecha te gustaría? (Ej: mañana, " . Carbon::today()->addDays(2)->locale('es')->isoFormat('D [de] MMMM') . ")", $contextos);
            }

            try {
                $fechaCarbon = Carbon::parse($this->datosReserva['fecha'])->startOfDay();
                $hoy = Carbon::today()->startOfDay();
                $fechaLimite = $hoy->copy()->addDays(self::MAX_DIAS_ANTICIPACION_RESERVA);

                if ($fechaCarbon->isPast() && !$fechaCarbon->isToday()) {
                    $this->datosReserva['fecha'] = null; // Limpiar y volver a pedir
                    $contextos = $this->generarNombresContextosActivos(['reserva_cancha_esperando_fecha']);
                    return $this->prepararRespuesta("Esa fecha ya pasó. Por favor, indica una fecha a partir de hoy.", $contextos);
                }
                if ($fechaCarbon->gt($fechaLimite)) {
                    $this->datosReserva['fecha'] = null; // Limpiar y volver a pedir
                    $contextos = $this->generarNombresContextosActivos(['reserva_cancha_esperando_fecha']);
                    $mensajeLimite = "Solo puedes reservar con un máximo de " . self::MAX_DIAS_ANTICIPACION_RESERVA . " días de anticipación. La última fecha sería el " . $fechaLimite->locale('es')->isoFormat('D [de] MMMM') . ". ¿Qué fecha eliges?";
                    return $this->prepararRespuesta($mensajeLimite, $contextos);
                }
                $this->datosReserva['fecha'] = $fechaCarbon->toDateString(); // Guardar normalizada
                $this->datosReserva['step'] = 'esperando_hora_inicio'; // Avanzar
            } catch (InvalidFormatException $e) {
                $this->datosReserva['fecha'] = null;
                $contextos = $this->generarNombresContextosActivos(['reserva_cancha_esperando_fecha']);
                return $this->prepararRespuesta("No entendí la fecha. ¿Podrías decirla de nuevo? (Ej: mañana, próximo martes)", $contextos);
            }
        }

        // 2. PROCESAR/PEDIR HORA DE INICIO
        if ($this->datosReserva['step'] === 'esperando_hora_inicio') {
            if (!$this->datosReserva['hora_inicio'] && (isset($currentDialogflowParams['hora_inicio']) || isset($currentDialogflowParams['horaini']))) {
                try {
                    $this->datosReserva['hora_inicio'] = Carbon::parse($currentDialogflowParams['hora_inicio'] ?? $currentDialogflowParams['horaini'])->format('H:i:s');
                } catch (InvalidFormatException $e) { /* se manejará abajo */
                }
            }

            if (!$this->datosReserva['hora_inicio']) {
                $disponibilidadMsg = $this->consultaDisponibilidadHandler->handle(['fecha' => $this->datosReserva['fecha']], $this->senderId, 'consulta.disponibilidad');
                $fechaFormateada = Carbon::parse($this->datosReserva['fecha'])->locale('es')->isoFormat('dddd D [de] MMMM');
                $mensaje = "Para el {$fechaFormateada}:\n{$disponibilidadMsg}\n\n¿A qué hora quieres iniciar tu reserva? (ej. 09:00, 14:30)";
                $contextos = $this->generarNombresContextosActivos(['reserva_cancha_esperando_hora_inicio']);
                return $this->prepararRespuesta($mensaje, $contextos, 'text');
            }

            try {
                $horaInicioCarbon = Carbon::parse($this->datosReserva['hora_inicio']); // Formato H:i:s
                $minutos = (int) $horaInicioCarbon->format('i');

                if ($minutos % self::INTERVALO_MINUTOS_RESERVA !== 0) {
                    $this->datosReserva['hora_inicio'] = null;
                    $mensajeError = "La hora de inicio debe ser en intervalos de " . self::INTERVALO_MINUTOS_RESERVA . " minutos (ej: 08:00, 08:30). ";
                    $disponibilidadMsg = $this->consultaDisponibilidadHandler->handle(['fecha' => $this->datosReserva['fecha']], $this->senderId, 'consulta.disponibilidad');
                    $fechaFormateada = Carbon::parse($this->datosReserva['fecha'])->locale('es')->isoFormat('dddd D [de] MMMM');
                    $mensajeCompleto = "Para el {$fechaFormateada}:\n{$disponibilidadMsg}\n\nPor favor, ¿a qué hora quieres iniciar?";
                    $contextos = $this->generarNombresContextosActivos(['reserva_cancha_esperando_hora_inicio']);
                    return $this->prepararRespuesta($mensajeCompleto, $contextos);
                }

                $horaDelDia = (int) $horaInicioCarbon->format('H');
                $minutosDelDia = (int) $horaInicioCarbon->format('i');
                $horaInicioAbsoluta = $horaDelDia * 60 + $minutosDelDia;
                $horaAperturaAbsoluta = self::HORA_INICIO_OPERACION * 60;
                // Última hora para empezar y completar la duración mínima sin pasar HORA_FIN_OPERACION
                $horaCierreAbsoluta = self::HORA_FIN_OPERACION * 60;
                $ultimaHoraInicioAbsoluta = $horaCierreAbsoluta - self::MIN_DURACION_RESERVA_MINUTOS;


                if ($horaInicioAbsoluta < $horaAperturaAbsoluta || $horaInicioAbsoluta > $ultimaHoraInicioAbsoluta) {
                    $this->datosReserva['hora_inicio'] = null;
                    $mensajeError = "Nuestras horas de reserva son de " . sprintf('%02d:00', self::HORA_INICIO_OPERACION) . " a " . sprintf('%02d:%02d', floor($ultimaHoraInicioAbsoluta / 60), $ultimaHoraInicioAbsoluta % 60) . " (para terminar a las " . sprintf('%02d:00', self::HORA_FIN_OPERACION) . "). ";
                    $disponibilidadMsg = $this->consultaDisponibilidadHandler->handle(['fecha' => $this->datosReserva['fecha']], $this->senderId, 'consulta.disponibilidad');
                    $fechaFormateada = Carbon::parse($this->datosReserva['fecha'])->locale('es')->isoFormat('dddd D [de] MMMM');
                    $mensajeCompleto = $mensajeError . "Para el {$fechaFormateada}:\n{$disponibilidadMsg}\n\n¿Qué hora eliges?";
                    $contextos = $this->generarNombresContextosActivos(['reserva_cancha_esperando_hora_inicio']);
                    return $this->prepararRespuesta($mensajeCompleto, $contextos);
                }

                if (Carbon::parse($this->datosReserva['fecha'])->isToday() && $horaInicioCarbon->isPast()) {
                    $this->datosReserva['hora_inicio'] = null;
                    $mensajeError = "Esa hora ya pasó hoy. ";
                    $disponibilidadMsg = $this->consultaDisponibilidadHandler->handle(['fecha' => $this->datosReserva['fecha']], $this->senderId, 'consulta.disponibilidad');
                    $fechaFormateada = Carbon::parse($this->datosReserva['fecha'])->locale('es')->isoFormat('dddd D [de] MMMM');
                    $mensajeCompleto = $mensajeError . "Para el {$fechaFormateada}:\n{$disponibilidadMsg}\n\nPor favor, ¿a qué hora quieres iniciar?";
                    $contextos = $this->generarNombresContextosActivos(['reserva_cancha_esperando_hora_inicio']);
                    return $this->prepararRespuesta($mensajeCompleto, $contextos);
                }

                $this->datosReserva['hora_inicio'] = $horaInicioCarbon->format('H:i:s');
                $this->datosReserva['step'] = 'esperando_duracion_o_fin';
            } catch (InvalidFormatException $e) {
                $this->datosReserva['hora_inicio'] = null;
                $disponibilidadMsg = $this->consultaDisponibilidadHandler->handle(['fecha' => $this->datosReserva['fecha']], $this->senderId, 'consulta.disponibilidad');
                $fechaFormateada = Carbon::parse($this->datosReserva['fecha'])->locale('es')->isoFormat('dddd D [de] MMMM');
                $mensaje = "La hora de inicio no es válida. Para el {$fechaFormateada}:\n{$disponibilidadMsg}\n\nPor favor, ¿a qué hora quieres iniciar?";
                $contextos = $this->generarNombresContextosActivos(['reserva_cancha_esperando_hora_inicio']);
                return $this->prepararRespuesta($mensaje, $contextos);
            }
        }

        // 3. PROCESAR/PEDIR DURACIÓN O HORA DE FIN
        if ($this->datosReserva['step'] === 'esperando_duracion_o_fin') {
            if (empty($this->datosReserva['duracion']) && empty($this->datosReserva['hora_fin'])) {
                // Tomar de los parámetros si se proporcionó con la acción de hora inicio, duración o fin
                if (isset($currentDialogflowParams['duracion'])) {
                    $this->datosReserva['duracion'] = $currentDialogflowParams['duracion'];
                } elseif (isset($currentDialogflowParams['hora_fin']) || isset($currentDialogflowParams['horafin'])) {
                    $this->datosReserva['hora_fin'] = $currentDialogflowParams['hora_fin'] ?? $currentDialogflowParams['horafin'];
                }
            }

            if (empty($this->datosReserva['duracion']) && empty($this->datosReserva['hora_fin'])) {
                $fechaFormateada = Carbon::parse($this->datosReserva['fecha'])->locale('es')->isoFormat('D [de] MMMM');
                $horaInicioFormateada = Carbon::parse($this->datosReserva['hora_inicio'])->format('H:i');
                $mensaje = "Reserva para el {$fechaFormateada} a las {$horaInicioFormateada}. ¿Por cuánto tiempo (ej: 1 hora, 1h 30m, máx 3h) o hasta qué hora?";
                $contextos = $this->generarNombresContextosActivos(['reserva_cancha_esperando_duracion_o_fin']);
                return $this->prepararRespuesta($mensaje, $contextos);
            }
            try {
                $this->calcularIntervaloCompleto(); // Este método ahora incluye todas las validaciones de duración y hora fin
                $this->datosReserva['step'] = 'listo_para_nombre_o_confirmacion';
            } catch (\InvalidArgumentException $e) {
                $this->datosReserva['duracion'] = null;
                $this->datosReserva['hora_fin'] = null;
                $contextos = $this->generarNombresContextosActivos(['reserva_cancha_esperando_duracion_o_fin']);
                return $this->prepararRespuesta($e->getMessage() . " Por favor, indícalo de nuevo.", $contextos);
            }
        }

        // 4. DECIDIR SI PEDIR NOMBRE O IR A CONFIRMACIÓN
        if ($this->datosReserva['step'] === 'listo_para_nombre_o_confirmacion') {
            // ... (lógica para obtener $cliente como la tenías) ...
            $datosClientePayload = [];
            if (!empty($this->datosReserva['nombre_cliente_temporal'])) {
                $datosClientePayload['nombre'] = $this->datosReserva['nombre_cliente_temporal'];
            } elseif (!empty($this->datosReserva['user_profile_name'])) {
                $datosClientePayload['nombre_perfil_whatsapp'] = $this->datosReserva['user_profile_name'];
            }
            $resultadoCliente = $this->clienteService->findOrCreateByTelefono($this->senderId, $datosClientePayload);
            $cliente = $resultadoCliente['cliente'];

            if (!$cliente) { /* ... (manejar error de cliente) ... */
            }

            // Actualizar nombre de perfil si no teníamos nombre explícito aún y el cliente no tiene nombre
            if (empty($this->datosReserva['nombre_cliente_temporal']) && !empty($this->datosReserva['user_profile_name']) && empty($cliente->nombre)) {
                $this->clienteService->actualizarDatosCliente($this->senderId, ['nombre' => $this->datosReserva['user_profile_name']]);
                $cliente->refresh();
            }

            if (($resultadoCliente['is_new_requiring_data'] || empty($cliente->nombre)) && empty($this->datosReserva['nombre_cliente_temporal'])) {
                $this->datosReserva['step'] = 'esperando_nombre';
            } else {
                $this->datosReserva['step'] = 'esperando_confirmacion';
            }
        }

        // 5. PROCESAR/PEDIR NOMBRE
        if ($this->datosReserva['step'] === 'esperando_nombre') {
            if (empty($this->datosReserva['nombre_cliente_temporal']) && (isset($currentDialogflowParams['nombre_cliente']) || (isset($currentDialogflowParams['person']) && isset($currentDialogflowParams['person']['name'])))) {
                $nombreTemp = $currentDialogflowParams['nombre_cliente'] ?? $currentDialogflowParams['person']['name'];
                $this->datosReserva['nombre_cliente_temporal'] = trim($nombreTemp);
            }

            if (empty($this->datosReserva['nombre_cliente_temporal'])) {
                $mensajeNombre = "Para completar tu reserva, necesito tu nombre completo por favor:";
                $contextos = $this->generarNombresContextosActivos(['reserva_cancha_esperando_nombre']);
                return $this->prepararRespuesta($mensajeNombre, $contextos);
            }
            if (!preg_match('/[a-zA-ZÁÉÍÓÚáéíóúÑñ\s]{3,}/', $this->datosReserva['nombre_cliente_temporal'])) { // Validación simple
                $this->datosReserva['nombre_cliente_temporal'] = null;
                $contextos = $this->generarNombresContextosActivos(['reserva_cancha_esperando_nombre']);
                return $this->prepararRespuesta("Ese nombre no parece válido. Por favor, ingresa tu nombre y apellido.", $contextos);
            }
            $this->datosReserva['step'] = 'esperando_confirmacion';
        }

        // 6. PEDIR CONFIRMACIÓN FINAL
        if ($this->datosReserva['step'] === 'esperando_confirmacion') {
            $this->datosReserva['confirmacion_pendiente'] = true;
            $cliente = $this->clienteService->findOrCreateByTelefono($this->senderId, [])['cliente'];
            $nombreMostrar = $cliente->nombre ?? $this->datosReserva['nombre_cliente_temporal'] ?? $this->datosReserva['user_profile_name'] ?? 'tú';
            if ($cliente && !empty($this->datosReserva['nombre_cliente_temporal']) && $this->datosReserva['nombre_cliente_temporal'] !== $cliente->nombre) {
                $this->clienteService->actualizarDatosCliente($this->senderId, ['nombre' => $this->datosReserva['nombre_cliente_temporal']]);
                $cliente->refresh();
            }
            $nombreMostrar = $cliente->nombre ?? $this->datosReserva['nombre_cliente_temporal'] ?? 'tú';

            $fechaF = Carbon::parse($this->datosReserva['fecha'])->locale('es')->isoFormat('dddd D [de] MMMM');
            $horaIF = Carbon::parse($this->datosReserva['hora_inicio'])->format('H:i');
            // Asegurarse de que hora_fin esté calculado ANTES de este punto
            if (empty($this->datosReserva['hora_fin'])) {
                try {
                    $this->calcularIntervaloCompleto();
                } catch (\Exception $e) { /* Debería haberse manejado antes, pero por si acaso */
                    Log::error("[{$this->cacheKey}] Error al calcular intervalo justo antes de confirmar: " . $e->getMessage());
                    $this->datosReserva['step'] = 'esperando_duracion_o_fin'; // Retroceder un paso
                    return $this->prepararRespuesta("Hubo un problema con los detalles de tiempo. Por favor, indica de nuevo la duración o la hora de finalización.", $this->generarNombresContextosActivos(['reserva_cancha_esperando_duracion_o_fin']));
                }
            }
            $horaFF = Carbon::parse($this->datosReserva['hora_fin'])->format('H:i');

            $confirmMsg = "Perfecto, {$nombreMostrar}. Resumen de tu solicitud:\n";
            $confirmMsg .= "Cancha para el {$fechaF}\n";
            $confirmMsg .= "Desde las {$horaIF} hasta las {$horaFF}.\n";
            $confirmMsg .= "¿Confirmas la reserva?";
            $contextos = $this->generarNombresContextosActivos(['reserva_cancha_esperando_confirmacion']);
            $payload = [
                'buttons' => [
                    ['id' => 'confirmar_reserva_si', 'title' => 'Sí, confirmar'], // Estos IDs deben mapear a acciones
                    ['id' => 'confirmar_cancelacion_reserva', 'title' => 'Cancelar']  // o texto que activen los intents correctos
                ]
            ];
            return $this->prepararRespuesta($confirmMsg, $contextos, 'interactive_buttons', $payload);
        }

        // Fallback si el step es desconocido
        Log::warning("[{$this->cacheKey}] Estado de flujo no manejado. Step: {$this->datosReserva['step']}. Reiniciando.");
        $this->clearSessionData(); // true para mantener info de reserva activa si la hubo y el usuario quiere verla.
        $this->datosReserva['step'] = 'inicio'; // Volver a empezar
        $contextos = $this->generarNombresContextosActivosParaLimpiar(['reserva_cancha_en_progreso']);
        return $this->prepararRespuesta("Parece que hubo un problema. Vamos a intentarlo de nuevo desde el principio. ¿Te gustaría hacer una reserva?", $contextos);
    }


    private function calcularIntervaloCompleto(): void
    {
        if (empty($this->datosReserva['fecha']) || empty($this->datosReserva['hora_inicio'])) {
            throw new \InvalidArgumentException("Falta la fecha o la hora de inicio para calcular el intervalo.");
        }

        $horaInicioCarbon = Carbon::parse($this->datosReserva['fecha'] . ' ' . $this->datosReserva['hora_inicio']);
        $minutosInicio = (int) $horaInicioCarbon->format('i');

        // Validar que la hora de inicio también cumpla el intervalo de 30 min (redundante si ya se validó, pero seguro)
        if ($minutosInicio % self::INTERVALO_MINUTOS_RESERVA !== 0) {
            throw new \InvalidArgumentException("La hora de inicio debe ser en intervalos de " . self::INTERVALO_MINUTOS_RESERVA . " minutos.");
        }

        $duracionEnMinutosCalculada = 0;
        if (!empty($this->datosReserva['duracion'])) {
            $duracionData = $this->datosReserva['duracion'];
            $horaFinCalculada = $horaInicioCarbon->copy();

            if (is_array($duracionData) && isset($duracionData['amount']) && isset($duracionData['unit'])) {
                $amount = (int) $duracionData['amount'];
                $unit = strtolower($duracionData['unit']);
                if (in_array($unit, ['h', 'hour', 'hours', 'hora']))
                    $duracionEnMinutosCalculada = $amount * 60;
                elseif (in_array($unit, ['min', 'minute', 'minutes']))
                    $duracionEnMinutosCalculada = $amount;
                else
                    throw new \InvalidArgumentException("Unidad de duración no entendida: {$duracionData['unit']}.");
            } elseif (is_string($duracionData)) {
                if (preg_match('/(\d+)\s*(hora|h|horas)/i', $duracionData, $matches))
                    $duracionEnMinutosCalculada = (int) $matches[1] * 60;
                elseif (preg_match('/(\d+)\s*(minuto|min|minutos)/i', $duracionData, $matches))
                    $duracionEnMinutosCalculada = (int) $matches[1];
                else
                    throw new \InvalidArgumentException("No entendí la duración: '{$duracionData}'. Usa '1 hora', '90 minutos', '2 horas y media'.");
                // Considerar parseo de "X horas y Y minutos"
                if (preg_match('/(\d+)\s*(?:hora|h|horas)\s*y\s*(\d+)\s*(?:minuto|min|minutos)/i', $duracionData, $matches)) {
                    $duracionEnMinutosCalculada = (int) $matches[1] * 60 + (int) $matches[2];
                } elseif (preg_match('/(\d+)\s*(?:hora|h|horas)/i', $duracionData, $matches)) {
                    $duracionEnMinutosCalculada = (int) $matches[1] * 60;
                } elseif (preg_match('/(\d+)\s*(?:minuto|min|minutos)/i', $duracionData, $matches)) {
                    $duracionEnMinutosCalculada = (int) $matches[1];
                } elseif (preg_match('/(\d+\.?\d*)\s*(?:hora|h|horas)/i', $duracionData, $matches)) { // Para decimales como 1.5 horas
                    $duracionEnMinutosCalculada = (int) ((float) $matches[1] * 60);
                } else {
                    throw new \InvalidArgumentException("No entendí la duración: '{$duracionData}'. Usa '1 hora', '90 minutos', '1 hora y 30 minutos'.");
                }
            } else {
                throw new \InvalidArgumentException("Formato de duración no reconocido.");
            }

            // Validar duración mínima y máxima
            if ($duracionEnMinutosCalculada < self::MIN_DURACION_RESERVA_MINUTOS) {
                throw new \InvalidArgumentException("La duración mínima de la reserva es de " . self::MIN_DURACION_RESERVA_MINUTOS . " minutos (1 hora).");
            }
            if ($duracionEnMinutosCalculada > self::MAX_DURACION_RESERVA_MINUTOS) {
                throw new \InvalidArgumentException("La duración máxima de la reserva es de " . self::MAX_DURACION_RESERVA_MINUTOS . " minutos (3 horas).");
            }
            // Validar que la duración sea múltiplo del intervalo
            if ($duracionEnMinutosCalculada % self::INTERVALO_MINUTOS_RESERVA !== 0) {
                throw new \InvalidArgumentException("La duración de la reserva debe ser en múltiplos de " . self::INTERVALO_MINUTOS_RESERVA . " minutos.");
            }

            $horaFinCalculada = $horaInicioCarbon->copy()->addMinutes($duracionEnMinutosCalculada);
            $this->datosReserva['hora_fin'] = $horaFinCalculada->format('H:i:s');
            // Actualizar $this->datosReserva['duracion'] a un formato consistente si es necesario, ej, minutos totales.
            // $this->datosReserva['duracion'] = $duracionEnMinutosCalculada; // O mantener el string original

        } elseif (!empty($this->datosReserva['hora_fin'])) {
            $horaFinCarbon = Carbon::parse($this->datosReserva['fecha'] . ' ' . $this->datosReserva['hora_fin']);
            $minutosFin = (int) $horaFinCarbon->format('i');

            if ($minutosFin % self::INTERVALO_MINUTOS_RESERVA !== 0) {
                throw new \InvalidArgumentException("La hora de finalización debe ser en intervalos de " . self::INTERVALO_MINUTOS_RESERVA . " minutos.");
            }
            if ($horaFinCarbon->lte($horaInicioCarbon)) {
                throw new \InvalidArgumentException("La hora de finalización debe ser posterior a la hora de inicio.");
            }

            $duracionEnMinutosCalculada = $horaInicioCarbon->diffInMinutes($horaFinCarbon);

            if ($duracionEnMinutosCalculada < self::MIN_DURACION_RESERVA_MINUTOS) {
                throw new \InvalidArgumentException("La duración mínima de la reserva es de " . self::MIN_DURACION_RESERVA_MINUTOS . " minutos (1 hora).");
            }
            if ($duracionEnMinutosCalculada > self::MAX_DURACION_RESERVA_MINUTOS) {
                throw new \InvalidArgumentException("La duración máxima de la reserva es de " . self::MAX_DURACION_RESERVA_MINUTOS . " minutos (3 horas).");
            }
            // Validar que la duración (calculada de hora_fin) sea múltiplo del intervalo
            if ($duracionEnMinutosCalculada % self::INTERVALO_MINUTOS_RESERVA !== 0) {
                throw new \InvalidArgumentException("El intervalo de tiempo de la reserva debe ser en múltiplos de " . self::INTERVALO_MINUTOS_RESERVA . " minutos.");
            }

            // $this->datosReserva['duracion'] = $duracionEnMinutosCalculada; // O mantener como string
        } else {
            throw new \LogicException("Se intentó calcular intervalo sin duración ni hora fin.");
        }

        // Validar que la hora_fin no exceda HORA_FIN_OPERACION
        $horaFinFinal = Carbon::parse($this->datosReserva['fecha'] . ' ' . $this->datosReserva['hora_fin']);
        $horaCierreOperacion = Carbon::parse($this->datosReserva['fecha'] . ' ' . sprintf('%02d:00:00', self::HORA_FIN_OPERACION));

        if ($horaFinFinal->gt($horaCierreOperacion)) {
            throw new \InvalidArgumentException("La reserva no puede terminar después de las " . sprintf('%02d:00', self::HORA_FIN_OPERACION) . ".");
        }

        // Validar que la hora_fin no sea igual o anterior a hora_inicio (ya cubierto arriba pero por si acaso)
        if ($horaFinFinal->lte($horaInicioCarbon)) {
            throw new \InvalidArgumentException("El intervalo de la reserva no es válido (fin <= inicio).");
        }
    }

    private function intentarCrearReserva(): array
    {
        $this->datosReserva['confirmacion_pendiente'] = false;
        $nombreParaCliente = $this->datosReserva['nombre_cliente_temporal'] ?? $this->datosReserva['user_profile_name'] ?? null;
        $cliente = $this->clienteService->findOrCreateByTelefono($this->senderId, ['nombre' => $nombreParaCliente])['cliente'];

        if (!$cliente) {
            $this->clearSessionData();
            $this->datosReserva['step'] = 'finalizado_o_cancelado';
            return $this->prepararRespuesta("Error: No se pudo obtener la información del cliente.", [], 'text');
        }
        // Asegurar que el nombre esté actualizado si se proporcionó explícitamente
        if (!empty($this->datosReserva['nombre_cliente_temporal']) && $this->datosReserva['nombre_cliente_temporal'] !== $cliente->nombre) {
            $this->clienteService->actualizarDatosCliente($this->senderId, ['nombre' => $this->datosReserva['nombre_cliente_temporal']]);
            $cliente->refresh();
        }

        try {
            if (empty($this->datosReserva['hora_fin'])) { // Recalcular por si solo teníamos duración
                $this->calcularIntervaloCompleto();
            }
        } catch (\Exception $e) {
            $this->datosReserva['step'] = 'esperando_duracion_o_fin';
            $this->datosReserva['duracion'] = null;
            $this->datosReserva['hora_fin'] = null;
            $contextos = $this->generarNombresContextosActivos(['reserva_cancha_esperando_duracion_o_fin']);
            return $this->prepararRespuesta("Hubo un problema al procesar la duración/hora de fin: " . $e->getMessage() . ". Por favor, indícalo de nuevo.", $contextos);
        }

        $resultadoReserva = $this->reservaService->crearReservaEnPrimeraCanchaLibre(
            $cliente->cliente_id,
            $this->datosReserva['fecha'],
            $this->datosReserva['hora_inicio'],
            $this->datosReserva['duracion'], // Pasar el dato de duración (string u objeto)
            $this->datosReserva['hora_fin'],   // Pasar la hora_fin calculada
            $cliente
        );

        if ($resultadoReserva['success']) {
            $this->clearSessionData();
            $this->datosReserva['step'] = 'finalizado_o_cancelado';
            $contextosLimpios = $this->generarNombresContextosActivosParaLimpiar(['reserva_cancha_en_progreso', 'reserva_cancha_esperando_confirmacion']);
            return $this->prepararRespuesta($resultadoReserva['message'], $contextosLimpios, 'text');
        } else {
            $mensajeError = $resultadoReserva['message'];
            $contextosReintento = [];
            if (str_contains($mensajeError, 'no hay canchas disponibles') || str_contains($mensajeError, 'conflicto')) {
                $this->datosReserva['step'] = 'esperando_hora_inicio';
                $this->datosReserva['hora_inicio'] = null;
                $this->datosReserva['hora_fin'] = null;
                $this->datosReserva['duracion'] = null;
                $contextosReintento = $this->generarNombresContextosActivos(['reserva_cancha_esperando_hora_inicio']);
                $mensajeError .= "\n¿Te gustaría intentar con otra hora para el " . Carbon::parse($this->datosReserva['fecha'])->locale('es')->isoFormat('D [de] MMMM') . ", o cambiar la fecha?";
            } elseif (str_contains($mensajeError, 'fechas pasadas') || str_contains($mensajeError, 'horas pasadas')) {
                $this->datosReserva['fecha'] = null;
                $this->datosReserva['hora_inicio'] = null;
                $this->datosReserva['hora_fin'] = null;
                $this->datosReserva['duracion'] = null;
                $this->datosReserva['step'] = 'esperando_fecha';
                $contextosReintento = $this->generarNombresContextosActivos(['reserva_cancha_esperando_fecha']);
                $mensajeError .= " Por favor, indícame una nueva fecha.";
            } else {
                // Para otros errores, limpiar todo y ofrecer empezar de nuevo o contactar.
                $this->clearSessionData();
                $this->datosReserva['step'] = 'finalizado_o_cancelado';
                $contextosReintento = $this->generarNombresContextosActivosParaLimpiar(['reserva_cancha_en_progreso']);
                $mensajeError .= " Hubo un problema con tu solicitud. Por favor, intenta de nuevo o contacta a recepción.";
            }
            return $this->prepararRespuesta($mensajeError, $contextosReintento, 'text');
        }
    }

    /**
     * Prepara la respuesta para el WhatsappController.
     * Incluye 'outputContextsToSetActive' para que el controller los guarde en caché
     * y los envíe a Dialogflow en la siguiente solicitud.
     */
    private function prepararRespuesta(string $fulfillmentText, array $outputContextsToSetActive = [], string $messageType = 'text', array $payload = []): array
    {
        $message = [
            'fulfillmentText' => $fulfillmentText,
            'message_type' => $messageType,
            'payload' => $payload,
        ];

        return [
            'messages_to_send' => [$message], // Siempre un array de mensajes
            'outputContextsToSetActive' => $outputContextsToSetActive
        ];
    }

    /**
     * Genera la estructura de contextos para guardar en caché y enviar a Dialogflow.
     */
    private function generarNombresContextosActivos(array $specificContextNames): array
    {
        $projectId = trim(config('dialogflow.project_id'), '/'); // CRÍTICO: quitar barras
        // $this->senderId ya debe estar normalizado (sin 'whatsapp:')
        $sessionId = 'whatsapp-' . $this->senderId; // CONSISTENTE con lo que usa whatsappController para llamar a DF

        $contextsParaActivar = [];
        // Contexto general del flujo
        if ($this->datosReserva['step'] !== 'inicio' && $this->datosReserva['step'] !== 'finalizado_o_cancelado') {
            $contextsParaActivar[] = [
                'name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/reserva_cancha_en_progreso",
                'lifespanCount' => 10, // Vida útil más larga para el contexto general
            ];
        }

        foreach ($specificContextNames as $name) {
            $cleanName = trim($name, '/'); // Nombre del contexto específico
            $contextsParaActivar[] = [
                'name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/{$cleanName}",
                'lifespanCount' => 2, // Vida útil corta para contextos de paso
            ];
        }
        return $contextsParaActivar;
    }

    private function generarNombresContextosActivosParaLimpiar(array $contextNamesToClear): array
    {
        $projectId = trim(config('dialogflow.project_id'), '/');
        $sessionId = 'whatsapp-' . $this->senderId;
        $contexts = [];
        foreach ($contextNamesToClear as $name) {
            $cleanName = trim($name, '/');
            // Para limpiar, se establece lifespanCount a 0
            $contexts[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/{$cleanName}", 'lifespanCount' => 0];
        }
        // También limpiar el contexto general del flujo si aplica
        if ($this->datosReserva['step'] === 'finalizado_o_cancelado' && !in_array('reserva_cancha_en_progreso', array_map('basename', array_column($contexts, 'name')))) {
            $contexts[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/reserva_cancha_en_progreso", 'lifespanCount' => 0];
        }
        return $contexts;
    }
}