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
use App\Models\Reserva;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use URL;
use App\Http\Controllers\Chatbot\whatsappController;
use InvalidArgumentException;



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
        'reserva_id_pendiente' => null,
        'monto_reserva_pendiente' => null,
        'decision_sobre_reserva_existente_tomada' => false, //
        'previous_step_before_cancel_prompt' => null, //
        'cliente_id' => null, // <--- NUEVO
        'cliente_obj' => null, // <--- NUEVO
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
    private function loadSessionData(string $normalizedSenderId): void
    {
        $this->senderId = $normalizedSenderId;
        $this->cacheKey = self::CACHE_KEY_PREFIX . $this->senderId;
        $cachedData = Cache::get($this->cacheKey);
        $defaultData = [
            'step' => 'inicio',
            'fecha' => null,
            'hora_inicio' => null,
            'hora_fin' => null,
            'duracion' => null,
            'nombre_cliente_temporal' => null,
            'confirmacion_pendiente' => false,
            'user_profile_name' => null,
            'decision_sobre_reserva_existente_tomada' => false,
            'reserva_id_pendiente' => null,
            'monto_reserva_pendiente' => null,
            'previous_step_before_cancel_prompt' => null,
            'cliente_id' => null,
            'cliente_obj' => null, // <--- NUEVO
        ];
        $this->datosReserva = $cachedData ? array_merge($defaultData, $cachedData) : $defaultData; //
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


    public function handle(array $parameters, string $normalizedSenderId, ?string $action = null): array
    {
        $this->loadSessionData($normalizedSenderId); //
        Log::debug("[{$this->cacheKey}] Orquestador Invocado. Action: {$action}. Step: {$this->datosReserva['step']}."); //
        Log::debug("[{$this->cacheKey}] Datos en caché ANTES de procesar:", $this->datosReserva);

        // Guardar user_profile_name si viene en los parámetros (del whatsappController)
        if (isset($parameters['user_profile_name']) && empty($this->datosReserva['user_profile_name'])) { //
            $this->datosReserva['user_profile_name'] = $parameters['user_profile_name']; //
        }
        if ($action === 'reservaCancha.confirmarNuevaReservaPeseAExistente') { //
            $this->datosReserva['decision_sobre_reserva_existente_tomada'] = true; //
            $this->datosReserva['step'] = 'esperando_fecha'; //
            // Limpiar datos de reserva si venían con el intent iniciar //
            $this->datosReserva['fecha'] = null;
            $this->datosReserva['hora_inicio'] = null; //
            $this->datosReserva['hora_fin'] = null;
            $this->datosReserva['duracion'] = null; //
        } else {
            $this->procesarParametrosEntrantes($parameters);
        }


        Log::debug("[{$this->cacheKey}] Datos en caché DESPUÉS de fusionar params:", $this->datosReserva);

        $respuesta = $this->gestionarFlujoReserva($action, $parameters); //
        $this->saveSessionData(); //
        Log::debug("[{$this->cacheKey}] Datos en caché GUARDADOS:", $this->datosReserva);
        Log::debug("[{$this->cacheKey}] Respuesta del Orquestador (para WhatsappController):", $respuesta);

        return $respuesta; //
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
        // --- 1. MANEJAR ACCIONES EXPLÍCITAS Y DE ALTA PRIORIDAD ---
        if ($action === 'reservaCancha.cancelar' || $action === 'reservaCancha.confirmarCancelacionProcesoSi') {
            $this->clearSessionData();
            $this->datosReserva['step'] = 'finalizado_o_cancelado';
            return $this->prepararRespuesta("Entendido, he cancelado el proceso de reserva.", $this->generarNombresContextosActivosParaLimpiar(['reserva_cancha_en_progreso']));
        }
        if ($action === 'reservaCancha.confirmarSi' && $this->datosReserva['confirmacion_pendiente']) {
            return $this->iniciarFlujoDePago();
        }
        if ($action === 'reservaCancha.confirmarNo' && $this->datosReserva['confirmacion_pendiente']) {
            $this->datosReserva['confirmacion_pendiente'] = false;
            $this->datosReserva['step'] = 'modificando_reserva';
            $this->datosReserva['hora_fin'] = null;
            $this->datosReserva['duracion'] = null;
            return $this->prepararRespuesta("Entendido. ¿Qué deseas cambiar: la fecha, la hora de inicio, o la duración?", $this->generarNombresContextosActivos(['reserva_cancha_modificando']));
        }
        if ($action === 'reservaCancha.enviarComprobante') {
            return $this->manejarComprobante($currentDialogflowParams);
        }

        // --- 2. VERIFICACIÓN DE RESERVA EXISTENTE (SOLO AL INICIO) ---
        if ($action === 'reservaCancha.iniciar' && !$this->datosReserva['decision_sobre_reserva_existente_tomada']) {
            $this->datosReserva['decision_sobre_reserva_existente_tomada'] = true;
            $cliente = $this->clienteService->findOrCreateByTelefono($this->senderId, ['nombre_perfil_whatsapp' => $this->datosReserva['user_profile_name']])['cliente'];
            if ($cliente) {
                $reservasActivas = $this->reservaService->getReservasActivasFuturasPorCliente($cliente->cliente_id);
                if ($reservasActivas && !$reservasActivas->isEmpty()) {
                    $this->datosReserva['step'] = 'esperando_decision_reserva_existente';
                    $mensaje = "Hola " . ($cliente->nombre ?? 'estimado cliente') . "! Veo que ya tienes " . ($reservasActivas->count() > 1 ? "estas reservas" : "esta reserva") . ":\n";
                    foreach ($reservasActivas as $idx => $infoRes) {
                        $fechaRes = Carbon::parse($infoRes->fecha)->locale('es')->isoFormat('dddd D MMM');
                        $horaInicioRes = Carbon::parse($infoRes->hora_inicio)->format('H:i');
                        $horaFinRes = Carbon::parse($infoRes->hora_fin)->format('H:i');
                        $canchaNombre = $infoRes->cancha->nombre ?? 'N/A';
                        $mensaje .= ($idx + 1) . ". {$canchaNombre} el {$fechaRes} de {$horaInicioRes} a {$horaFinRes}.\n";
                    }
                    $mensaje .= "\n¿Aún así quieres hacer una nueva reserva?";
                    $payload = ['buttons' => [['id' => 'si_proceder_nueva_reserva', 'title' => 'Sí, reservar otra'], ['id' => 'menu', 'title' => 'No, gracias']]];
                    return $this->prepararRespuesta($mensaje, $this->generarNombresContextosActivos(['reserva_cancha_decision_nueva_pese_a_existente']), 'interactive_buttons', $payload);
                }
            }
        }
        if ($action === 'reservaCancha.confirmarNuevaReservaPeseAExistente') {
            $this->datosReserva['step'] = 'esperando_fecha';
            $this->datosReserva['fecha'] = null;
            $this->datosReserva['hora_inicio'] = null;
            $this->datosReserva['hora_fin'] = null;
            $this->datosReserva['duracion'] = null;
        }

        // --- 3. GESTIÓN SECUENCIAL DE DATOS FALTANTES ---

        // 3.1 FECHA
        if (empty($this->datosReserva['fecha'])) {
            return $this->prepararRespuesta("Para tu reserva, ¿para qué fecha te gustaría? (Ej: mañana, próximo martes)", $this->generarNombresContextosActivos(['reserva_cancha_esperando_fecha']));
        }
        try {
            $fechaCarbon = Carbon::parse($this->datosReserva['fecha'])->startOfDay();
            $hoy = Carbon::today()->startOfDay();
            $fechaLimite = $hoy->copy()->addDays(self::MAX_DIAS_ANTICIPACION_RESERVA);
            if ($fechaCarbon->isPast() && !$fechaCarbon->isToday())
                throw new InvalidArgumentException("Esa fecha ya pasó.");
            if ($fechaCarbon->gt($fechaLimite))
                throw new InvalidArgumentException("Solo puedes reservar con máximo " . self::MAX_DIAS_ANTICIPACION_RESERVA . " días de antelación (hasta el " . $fechaLimite->locale('es')->isoFormat('D MMM') . ").");
        } catch (\Exception $e) {
            $this->datosReserva['fecha'] = null;
            return $this->prepararRespuesta($e->getMessage() . " Por favor, indica una fecha válida.", $this->generarNombresContextosActivos(['reserva_cancha_esperando_fecha']));
        }

        // 3.2 HORA DE INICIO
        if (empty($this->datosReserva['hora_inicio'])) {
            $handlerConsulta = app(ConsultaDisponibilidadCanchaHandler::class);
            $respuestaConsultaArray = $handlerConsulta->handle(['fecha' => $this->datosReserva['fecha']], $this->senderId, 'consulta.disponibilidad');
            $disponibilidadMsg = $respuestaConsultaArray['fulfillmentText'] ?? "Consultando disponibilidad...";
            $fechaFormateada = Carbon::parse($this->datosReserva['fecha'])->locale('es')->isoFormat('dddd D [de] MMMM');
            $mensaje = "Para el {$fechaFormateada}:\n{$disponibilidadMsg}\n\n¿A qué hora quieres iniciar tu reserva? (ej. 09:00, 14:30)";
            return $this->prepararRespuesta($mensaje, $this->generarNombresContextosActivos(['reserva_cancha_esperando_hora_inicio']));
        }
        try {
            $horaInicioCarbon = Carbon::parse($this->datosReserva['hora_inicio']);
            if ($horaInicioCarbon->format('i') % self::INTERVALO_MINUTOS_RESERVA !== 0)
                throw new InvalidArgumentException("La hora debe ser en intervalos de " . self::INTERVALO_MINUTOS_RESERVA . " min (ej: 08:00, 08:30).");
            $horaDelDia = (int) $horaInicioCarbon->format('H');
            $minutosDelDia = (int) $horaInicioCarbon->format('i');
            $horaInicioAbsoluta = $horaDelDia * 60 + $minutosDelDia;
            $horaAperturaAbsoluta = self::HORA_INICIO_OPERACION * 60;
            $ultimaHoraInicioAbsoluta = (self::HORA_FIN_OPERACION * 60) - self::MIN_DURACION_RESERVA_MINUTOS;
            if ($horaInicioAbsoluta < $horaAperturaAbsoluta || $horaInicioAbsoluta > $ultimaHoraInicioAbsoluta)
                throw new InvalidArgumentException("Nuestro horario es de " . sprintf('%02d:00', self::HORA_INICIO_OPERACION) . " hasta las " . sprintf('%02d:00', self::HORA_FIN_OPERACION) . ".");
            if (Carbon::parse($this->datosReserva['fecha'])->isToday() && $horaInicioCarbon->isPast())
                throw new InvalidArgumentException("Esa hora ya pasó hoy.");
        } catch (\Exception $e) {
            $this->datosReserva['hora_inicio'] = null;
            $mensajeError = ($e instanceof InvalidFormatException) ? "No entendí la hora que indicaste." : $e->getMessage();
            return $this->prepararRespuesta($mensajeError . " Por favor, elige una hora válida.", $this->generarNombresContextosActivos(['reserva_cancha_esperando_hora_inicio']));
        }

        // 3.3 DURACIÓN / HORA FIN
        if (empty($this->datosReserva['duracion']) && empty($this->datosReserva['hora_fin'])) {
            $mensaje = "Perfecto, reserva para las " . Carbon::parse($this->datosReserva['hora_inicio'])->format('H:i') . ". ¿Por cuánto tiempo (ej: 1 hora, 1h 30m, máx 3h) o hasta qué hora?";
            return $this->prepararRespuesta($mensaje, $this->generarNombresContextosActivos(['reserva_cancha_esperando_duracion_o_fin']));
        }
        try {
            $this->calcularIntervaloCompleto();
        } catch (InvalidArgumentException $e) {
            $this->datosReserva['duracion'] = null;
            $this->datosReserva['hora_fin'] = null;
            return $this->prepararRespuesta($e->getMessage() . " Por favor, indícalo de nuevo.", $this->generarNombresContextosActivos(['reserva_cancha_esperando_duracion_o_fin']));
        }

        // 3.4 CLIENTE (ID y Objeto)
        if (empty($this->datosReserva['cliente_id'])) {
            $datosParaFind = ['nombre_perfil_whatsapp' => $this->datosReserva['user_profile_name'] ?? null];
            $resultadoCliente = $this->clienteService->findOrCreateByTelefono($this->senderId, $datosParaFind);
            $cliente = $resultadoCliente['cliente'];
            if (!$cliente) {
                $this->clearSessionData();
                return $this->prepararRespuesta("Hubo un problema al identificarte. Por favor, intenta de nuevo más tarde.", $this->generarNombresContextosActivosParaLimpiar(['reserva_cancha_en_progreso']));
            }
            $this->datosReserva['cliente_id'] = $cliente->cliente_id;
            $this->datosReserva['cliente_obj'] = $cliente;

            // Si es nuevo y no se pudo obtener un buen nombre, o si es existente sin nombre, pedirlo.
            if ($resultadoCliente['is_new_requiring_data'] || empty($cliente->nombre)) {
                $this->datosReserva['step'] = 'esperando_nombre';
                $mensajeNombre = "Para completar tu reserva, ¿podrías indicarme tu nombre completo?";
                return $this->prepararRespuesta($mensajeNombre, $this->generarNombresContextosActivos(['reserva_cancha_esperando_nombre']));
            }
        }

        // 3.5 NOMBRE DEL CLIENTE (si se pidió explícitamente)
        if ($this->datosReserva['step'] === 'esperando_nombre') {
            if (empty($this->datosReserva['nombre_cliente_temporal'])) {
                return $this->prepararRespuesta("Por favor, necesito tu nombre completo para continuar.", $this->generarNombresContextosActivos(['reserva_cancha_esperando_nombre']));
            }
            if (!preg_match('/[a-zA-ZÁÉÍÓÚáéíóúÑñ\s]{3,}/', $this->datosReserva['nombre_cliente_temporal'])) {
                $this->datosReserva['nombre_cliente_temporal'] = null;
                return $this->prepararRespuesta("Ese nombre no parece válido. Por favor, ingresa tu nombre y apellido.", $this->generarNombresContextosActivos(['reserva_cancha_esperando_nombre']));
            }
            // Actualizar el cliente con el nombre proporcionado
            $this->clienteService->actualizarDatosCliente($this->senderId, ['nombre' => $this->datosReserva['nombre_cliente_temporal']]);
            $this->datosReserva['cliente_obj'] = $this->clienteService->findClienteByTelefono($this->senderId); // Refrescar obj en caché
        }

        // 4. CONFIRMACIÓN FINAL (Si todos los datos están listos y aún no se ha pedido confirmación)
        if (!$this->datosReserva['confirmacion_pendiente']) {
            $this->datosReserva['confirmacion_pendiente'] = true;
            $nombreMostrar = $this->datosReserva['cliente_obj']->nombre ?? 'tú';
            $fechaF = Carbon::parse($this->datosReserva['fecha'])->locale('es')->isoFormat('dddd D [de] MMMM');
            $horaIF = Carbon::parse($this->datosReserva['hora_inicio'])->format('H:i');
            $horaFF = Carbon::parse($this->datosReserva['hora_fin'])->format('H:i');
            $confirmMsg = "Perfecto, {$nombreMostrar}. Resumen de tu solicitud:\n";
            $confirmMsg .= "Cancha para el {$fechaF}\n";
            $confirmMsg .= "Desde las {$horaIF} hasta las {$horaFF}.\n";
            $confirmMsg .= "¿Confirmas para proceder al pago?";
            $payload = ['buttons' => [['id' => 'confirmar_reserva_si', 'title' => 'Sí, proceder al pago'], ['id' => 'cancelar_proceso_reserva', 'title' => 'Cancelar Proceso']]];
            return $this->prepararRespuesta($confirmMsg, $this->generarNombresContextosActivos(['reserva_cancha_esperando_confirmacion']), 'interactive_buttons', $payload);
        }

        // Fallback si la lógica llega aquí inesperadamente
        Log::warning("[{$this->cacheKey}] Estado de flujo no manejado. Datos actuales:", $this->datosReserva);
        $this->clearSessionData();
        return $this->prepararRespuesta("Parece que nos perdimos. ¿Empezamos de nuevo?", $this->generarNombresContextosActivosParaLimpiar(['reserva_cancha_en_progreso']));
    }


    private function calcularIntervaloCompleto(): void
    {
        if (empty($this->datosReserva['fecha']) || empty($this->datosReserva['hora_inicio'])) {
            throw new InvalidArgumentException("Falta la fecha o la hora de inicio para calcular el intervalo.");
        }

        $horaInicioCarbon = Carbon::parse($this->datosReserva['fecha'] . ' ' . $this->datosReserva['hora_inicio']);
        $minutosInicio = (int) $horaInicioCarbon->format('i');

        // Validar que la hora de inicio también cumpla el intervalo de 30 min (redundante si ya se validó, pero seguro)
        if ($minutosInicio % self::INTERVALO_MINUTOS_RESERVA !== 0) {
            throw new InvalidArgumentException("La hora de inicio debe ser en intervalos de " . self::INTERVALO_MINUTOS_RESERVA . " minutos.");
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
                    throw new InvalidArgumentException("Unidad de duración no entendida: {$duracionData['unit']}.");
            } elseif (is_string($duracionData)) {
                if (preg_match('/(\d+)\s*(hora|h|horas)/i', $duracionData, $matches))
                    $duracionEnMinutosCalculada = (int) $matches[1] * 60;
                elseif (preg_match('/(\d+)\s*(minuto|min|minutos)/i', $duracionData, $matches))
                    $duracionEnMinutosCalculada = (int) $matches[1];
                else
                    throw new InvalidArgumentException("No entendí la duración: '{$duracionData}'. Usa '1 hora', '90 minutos', '2 horas y media'.");
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
                    throw new InvalidArgumentException("No entendí la duración: '{$duracionData}'. Usa '1 hora', '90 minutos', '1 hora y 30 minutos'.");
                }
            } else {
                throw new InvalidArgumentException("Formato de duración no reconocido.");
            }

            // Validar duración mínima y máxima
            if ($duracionEnMinutosCalculada < self::MIN_DURACION_RESERVA_MINUTOS) {
                throw new InvalidArgumentException("La duración mínima de la reserva es de " . self::MIN_DURACION_RESERVA_MINUTOS . " minutos (1 hora).");
            }
            if ($duracionEnMinutosCalculada > self::MAX_DURACION_RESERVA_MINUTOS) {
                throw new InvalidArgumentException("La duración máxima de la reserva es de " . self::MAX_DURACION_RESERVA_MINUTOS . " minutos (3 horas).");
            }
            // Validar que la duración sea múltiplo del intervalo
            if ($duracionEnMinutosCalculada % self::INTERVALO_MINUTOS_RESERVA !== 0) {
                throw new InvalidArgumentException("La duración de la reserva debe ser en múltiplos de " . self::INTERVALO_MINUTOS_RESERVA . " minutos.");
            }

            $horaFinCalculada = $horaInicioCarbon->copy()->addMinutes($duracionEnMinutosCalculada);
            $this->datosReserva['hora_fin'] = $horaFinCalculada->format('H:i:s');
            // Actualizar $this->datosReserva['duracion'] a un formato consistente si es necesario, ej, minutos totales.
            // $this->datosReserva['duracion'] = $duracionEnMinutosCalculada; // O mantener el string original

        } elseif (!empty($this->datosReserva['hora_fin'])) {
            $horaFinCarbon = Carbon::parse($this->datosReserva['fecha'] . ' ' . $this->datosReserva['hora_fin']);
            $minutosFin = (int) $horaFinCarbon->format('i');

            if ($minutosFin % self::INTERVALO_MINUTOS_RESERVA !== 0) {
                throw new InvalidArgumentException("La hora de finalización debe ser en intervalos de " . self::INTERVALO_MINUTOS_RESERVA . " minutos.");
            }
            if ($horaFinCarbon->lte($horaInicioCarbon)) {
                throw new InvalidArgumentException("La hora de finalización debe ser posterior a la hora de inicio.");
            }

            $duracionEnMinutosCalculada = $horaInicioCarbon->diffInMinutes($horaFinCarbon);

            if ($duracionEnMinutosCalculada < self::MIN_DURACION_RESERVA_MINUTOS) {
                throw new InvalidArgumentException("La duración mínima de la reserva es de " . self::MIN_DURACION_RESERVA_MINUTOS . " minutos (1 hora).");
            }
            if ($duracionEnMinutosCalculada > self::MAX_DURACION_RESERVA_MINUTOS) {
                throw new InvalidArgumentException("La duración máxima de la reserva es de " . self::MAX_DURACION_RESERVA_MINUTOS . " minutos (3 horas).");
            }
            // Validar que la duración (calculada de hora_fin) sea múltiplo del intervalo
            if ($duracionEnMinutosCalculada % self::INTERVALO_MINUTOS_RESERVA !== 0) {
                throw new InvalidArgumentException("El intervalo de tiempo de la reserva debe ser en múltiplos de " . self::INTERVALO_MINUTOS_RESERVA . " minutos.");
            }

            // $this->datosReserva['duracion'] = $duracionEnMinutosCalculada; // O mantener como string
        } else {
            throw new \LogicException("Se intentó calcular intervalo sin duración ni hora fin.");
        }

        // Validar que la hora_fin no exceda HORA_FIN_OPERACION
        $horaFinFinal = Carbon::parse($this->datosReserva['fecha'] . ' ' . $this->datosReserva['hora_fin']);
        $horaCierreOperacion = Carbon::parse($this->datosReserva['fecha'] . ' ' . sprintf('%02d:00:00', self::HORA_FIN_OPERACION));

        if ($horaFinFinal->gt($horaCierreOperacion)) {
            throw new InvalidArgumentException("La reserva no puede terminar después de las " . sprintf('%02d:00', self::HORA_FIN_OPERACION) . ".");
        }

        // Validar que la hora_fin no sea igual o anterior a hora_inicio (ya cubierto arriba pero por si acaso)
        if ($horaFinFinal->lte($horaInicioCarbon)) {
            throw new InvalidArgumentException("El intervalo de la reserva no es válido (fin <= inicio).");
        }
    }

    private function iniciarFlujoDePago(): array
    {
        $this->datosReserva['confirmacion_pendiente'] = false;

        // --- INICIO DE LA CORRECCIÓN ---
        // Usar el cliente_id y cliente_obj guardados en la caché de sesión
        $clienteId = $this->datosReserva['cliente_id']; //
        $cliente = $this->datosReserva['cliente_obj']; //

        if (!$clienteId || !$cliente) {
            Log::error("[{$this->cacheKey}] Se intentó iniciar el pago pero no se encontró cliente_id o cliente_obj en caché.");
            $this->clearSessionData(); //
            return $this->prepararRespuesta("Hubo un error al recuperar tus datos de cliente. Por favor, intenta el proceso de reserva de nuevo.", $this->generarNombresContextosActivosParaLimpiar(['reserva_cancha_en_progreso'])); //
        }
        // --- FIN DE LA CORRECCIÓN ---

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

        $duracionParaServicio = null; // Asegurar que es null si no se calcula correctamente
        if (!empty($this->datosReserva['duracion'])) {
            // tu lógica para parsear this->datosReserva['duracion'] a $duracionParaServicio (array o null)
            if (is_string($this->datosReserva['duracion'])) {
                if (preg_match('/(\d+)\s*(hora|h)/i', $this->datosReserva['duracion'], $matches)) {
                    $duracionParaServicio = ['amount' => (int) $matches[1], 'unit' => 'hour'];
                } elseif (preg_match('/(\d+)\s*(min)/i', $this->datosReserva['duracion'], $matches)) {
                    $duracionParaServicio = ['amount' => (int) $matches[1], 'unit' => 'min'];
                }
            } elseif (is_array($this->datosReserva['duracion'])) {
                $duracionParaServicio = $this->datosReserva['duracion'];
            }
        }

        $resultadoCreacion = $this->reservaService->crearReservaEnPrimeraCanchaLibre(
            $this->datosReserva['cliente_id'], // Asumiendo que guardaste el id del cliente
            $this->datosReserva['fecha'],
            $this->datosReserva['hora_inicio'],
            $duracionParaServicio, // CORREGIDO: ahora es ?array
            $this->datosReserva['hora_fin'],
            $this->datosReserva['cliente_obj'] // Asumiendo que guardaste el objeto cliente
        );

        if ($resultadoCreacion['success'] && isset($resultadoCreacion['reserva'])) {
            $reservaPendiente = $resultadoCreacion['reserva'];
            $this->datosReserva['reserva_id_pendiente'] = $reservaPendiente->reserva_id;
            $this->datosReserva['monto_reserva_pendiente'] = $reservaPendiente->monto_total;
            $this->datosReserva['step'] = 'esperando_comprobante_reserva';

            $mensajes = [];
            $urlQrEstatico = URL::asset('image/qr_pago_club.png'); // Debes tener esta imagen en public/images/
            $captionQr = "¡Tu solicitud está registrada!\nRealiza el pago de *Bs. " . number_format((float) $reservaPendiente->monto_total, 2) . "* escaneando este QR.";

            $mensajes[] = ['fulfillmentText' => $captionQr, 'message_type' => 'image', 'payload' => ['image_url' => $urlQrEstatico, 'caption' => $captionQr]];
            $instrucciones = "Puedes pagar también a la cuenta XXXX-XXXX-XXXX.\nUna vez realizado el pago, por favor *envíame la foto o captura de pantalla de tu comprobante* para confirmar tu reserva.";
            $mensajes[] = ['fulfillmentText' => $instrucciones, 'message_type' => 'text'];

            return $this->prepararRespuestaConMultiplesMensajes($mensajes, $this->generarNombresContextosActivos(['reserva_esperando_comprobante']));
        } else {
            $this->clearSessionData();
            return $this->prepararRespuesta($resultadoCreacion['message'] ?? "No se pudo registrar tu solicitud de reserva.", $this->generarNombresContextosActivosParaLimpiar(['reserva_cancha_en_progreso']));
        }
    }
    // NUEVO MÉTODO PARA MANEJAR EL COMPROBANTE
    private function manejarComprobante(array $currentDialogflowParams): array
    {
        $mediaId = $currentDialogflowParams['media_id'] ?? null;
        $mimeType = $currentDialogflowParams['mime_type'] ?? null;
        if (!$mediaId || !Str::startsWith($mimeType, 'image/')) {
            return $this->prepararRespuesta("Parece que no enviaste una imagen. Por favor, envía la foto de tu comprobante de pago.", $this->generarNombresContextosActivos(['reserva_esperando_comprobante']));
        }

        $reservaIdPendiente = $this->datosReserva['reserva_id_pendiente'];
        if (!$reservaIdPendiente) {
            Log::error("[{$this->cacheKey}] Se recibió comprobante pero no hay reserva_id_pendiente en caché.");
            $this->clearSessionData();
            return $this->prepararRespuesta("Hubo un problema asociando tu comprobante. Por favor, contacta a administración.", $this->generarNombresContextosActivosParaLimpiar(['reserva_cancha_en_progreso']));
        }

        // --- INICIO DE LA LÓGICA DE DESCARGA REAL ---
        // Obtener una instancia del whatsappController para usar su método de descarga
        $whatsappDownloader = app(whatsappController::class);
        $rutaGuardada = $whatsappDownloader->descargarImagenWhatsapp($mediaId, "reserva");
        // --- FIN DE LA LÓGICA DE DESCARGA REAL ---

        if ($rutaGuardada) {
            $actualizado = $this->reservaService->asociarComprobanteAReserva($reservaIdPendiente, $rutaGuardada);
            if ($actualizado) {
                $mensajeAgradecimiento = "¡Gracias! Hemos recibido tu comprobante para la reserva de Bs. " . number_format((float) $this->datosReserva['monto_reserva_pendiente'], 2) . ".\nUn encargado lo verificará a la brevedad. Tu reserva está *pendiente de confirmación*.";
            } else {
                $mensajeAgradecimiento = "Recibí tu comprobante, pero hubo un error al guardarlo en tu registro de reserva. Por favor, contacta a administración mencionando tu reserva.";
            }
        } else {
            // Si la descarga falló
            $mensajeAgradecimiento = "Tuve problemas para descargar tu comprobante. Por favor, intenta enviarlo de nuevo o contacta a administración.";
            // Mantenemos al usuario en el mismo paso para que pueda reintentar
            $this->saveSessionData(); // Guardar el estado actual
            return $this->prepararRespuesta($mensajeAgradecimiento, $this->generarNombresContextosActivos(['reserva_esperando_comprobante']));
        }

        $this->clearSessionData();
        $this->datosReserva['step'] = 'finalizado_o_cancelado';
        return $this->prepararRespuesta($mensajeAgradecimiento, $this->generarNombresContextosActivosParaLimpiar(['reserva_cancha_en_progreso', 'reserva_esperando_comprobante']));
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
    private function prepararRespuestaConMultiplesMensajes(array $mensajesArray, array $outputContextsToSetActive = []): array
    {
        return [
            'messages_to_send' => $mensajesArray,
            'outputContextsToSetActive' => $outputContextsToSetActive
        ];
    }

    private function prepararRespuestaUnSoloMensaje(string $fulfillmentText, array $outputContextsToSetActive = [], string $message_type = 'text', array $payload = []): array
    {
        return $this->prepararRespuestaConMultiplesMensajes(
            [['fulfillmentText' => $fulfillmentText, 'message_type' => $message_type, 'payload' => $payload]],
            $outputContextsToSetActive
        );
    }
    /**
     * Genera la estructura de contextos para guardar en caché y enviar a Dialogflow.
     */
    private function generarNombresContextosActivos(array $specificContextNames): array
    {
        $projectId = trim(config('dialogflow.project_id'), '/');
        if (empty($projectId)) {
            Log::error("Project ID está vacío en generarNombresContextos");
            return [];
        }
        $sessionId = 'whatsapp-' . $this->senderId;
        $contexts = [];
        if (!in_array($this->datosReserva['step'], ['inicio', 'finalizado_o_cancelado'])) {
            $contexts[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/reserva_cancha_en_progreso", 'lifespanCount' => 10];
        }
        foreach ($specificContextNames as $name) {
            $contexts[] = ['name' => "projects/{$projectId}/agent/sessions/{$sessionId}/contexts/" . trim($name, '/'), 'lifespanCount' => 2];
        }
        return $contexts;
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