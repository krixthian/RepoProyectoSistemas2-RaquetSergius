<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ClienteService;
use App\Services\ReservaService;
use App\Http\Controllers\Chatbot\IntentHandlers\ConsultaDisponibilidadCanchaHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RealizarReservaCanchaHandler implements IntentHandlerInterface
{
    protected ClienteService $clienteService;
    protected ReservaService $reservaService;
    protected ConsultaDisponibilidadCanchaHandler $consultaDisponibilidadHandler;

    private const CACHE_TTL_MINUTES = 30;

    public function __construct(
        ClienteService $clienteService,
        ReservaService $reservaService,
        ConsultaDisponibilidadCanchaHandler $consultaDisponibilidadHandler
    ) {
        $this->clienteService = $clienteService;
        $this->reservaService = $reservaService;
        $this->consultaDisponibilidadHandler = $consultaDisponibilidadHandler;
    }

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        // Mantenemos la normalización que ya usas consistentemente
        if (strpos($phoneNumber, 'whatsapp:+') === 0) {
            return substr($phoneNumber, strlen('whatsapp:+'));
        }
        return preg_replace('/[^0-9+]/', '', $phoneNumber);
    }

    public function handle(array $parameters, string $senderId, string $action = null): array
    {
        $telefonoCliente = $this->normalizePhoneNumber($senderId);
        $reservaCacheKey = 'reserva_cache_' . $telefonoCliente;
        $datosReserva = Cache::get($reservaCacheKey, []);


        $datosReserva = array_merge([
            'fecha' => null,
            'hora_inicio' => null,
            'hora_fin' => null,
            'duracion' => null,
            'paso_actual' => 'inicio',
            'nombre_cliente_temporal' => null,
        ], $datosReserva);


        if (isset($parameters['fecha']) && !empty($parameters['fecha'])) {
            try {
                $datosReserva['fecha'] = Carbon::parse($parameters['fecha'])->toDateString();

                if ($datosReserva['paso_actual'] !== 'esperando_fecha') {
                    $datosReserva['hora_inicio'] = null;
                    $datosReserva['hora_fin'] = null;
                    $datosReserva['duracion'] = null;

                }
            } catch (\Exception $e) {
                Log::warning("[RealizarReservaHandler] Parámetro de fecha inválido de Dialogflow: " . $parameters['fecha']);
                // No sobreescribir si ya hay una fecha válida en caché. Si no, se pedirá.
            }
        }
        if (isset($parameters['horaini']) && !empty($parameters['horaini'])) { // Dialogflow puede usar 'horaini'
            try {
                $datosReserva['hora_inicio'] = Carbon::parse($parameters['horaini'])->format('H:i:s');
                // Si se da una nueva hora de inicio, resetear hora_fin/duracion
                $datosReserva['hora_fin'] = null;
                $datosReserva['duracion'] = null;
            } catch (\Exception $e) {
                Log::warning("[RealizarReservaHandler] Parámetro de hora_inicio inválido: " . $parameters['horaini']);
            }
        }
        if (isset($parameters['duracion']) && !empty($parameters['duracion'])) {
            // Dialogflow puede enviar duración como un objeto {"amount":1,"unit":"h"}
            // o como un string parseable. El ReservaService lo maneja.
            $datosReserva['duracion'] = $parameters['duracion'];
            $datosReserva['hora_fin'] = null; // Duración tiene prioridad sobre hora_fin si ambas se dan
        }
        if (isset($parameters['horafin']) && !empty($parameters['horafin']) && empty($datosReserva['duracion'])) { // Solo si no hay duración
            try {
                $datosReserva['hora_fin'] = Carbon::parse($parameters['horafin'])->format('H:i:s');
            } catch (\Exception $e) {
                Log::warning("[RealizarReservaHandler] Parámetro de hora_fin inválido: " . $parameters['horafin']);
            }
        }
        // Si se capturó un nombre del cliente
        if (isset($parameters['nombre_capturado']) && !empty(trim($parameters['nombre_capturado']))) {
            $datosReserva['nombre_cliente_temporal'] = trim($parameters['nombre_capturado']);
            if ($datosReserva['paso_actual'] === 'esperando_nombre_cliente') {
                $datosReserva['paso_actual'] = 'esperando_hora_fin_o_duracion'; // Avanzar al siguiente paso natural
            }
        }


        Log::debug("[RealizarReservaHandler] Datos para reserva (sender: {$senderId}): ", $datosReserva);
        Log::debug("[RealizarReservaHandler] Parámetros Dialogflow (sender: {$senderId}): ", $parameters);

        // --- Inicio del flujo de reserva ---

        // 1. Pedir Fecha si falta
        if (empty($datosReserva['fecha'])) {
            $datosReserva['paso_actual'] = 'esperando_fecha';
            Cache::put($reservaCacheKey, $datosReserva, now()->addMinutes(self::CACHE_TTL_MINUTES));
            return ['fulfillmentText' => 'Entendido, quieres hacer una reserva. ¿Para qué fecha, por favor? (Ej: mañana, 15 de mayo, próximo lunes)'];
        }

        // 2. Si tenemos fecha pero no hora_inicio, mostrar disponibilidad y pedir hora_inicio
        // Este paso se activa si venimos de un intent que solo dio fecha, o si RealizarReserva se activó con fecha.
        if (!empty($datosReserva['fecha']) && empty($datosReserva['hora_inicio'])) {
            $datosReserva['paso_actual'] = 'esperando_hora_inicio';
            Cache::put($reservaCacheKey, $datosReserva, now()->addMinutes(self::CACHE_TTL_MINUTES));

            // Llamar a la lógica de ConsultaDisponibilidadCanchaHandler
            // Se asume que este handler devuelve un string directamente.
            $respuestaDisponibilidad = $this->consultaDisponibilidadHandler->handle(['fecha' => $datosReserva['fecha']], $senderId);

            $fechaFormateadaUser = Carbon::parse($datosReserva['fecha'])->locale('es')->isoFormat('dddd D [de] MMMM');
            $mensaje = "Para el {$fechaFormateadaUser}:\n{$respuestaDisponibilidad}";
            $mensaje .= "\n\nPor favor, ¿a qué hora quieres iniciar tu reserva? (Ej: 3 PM, 15:00)";
            return ['fulfillmentText' => $mensaje];
        }

        // 3. Si tenemos fecha y hora_inicio, pero falta hora_fin Y duración, pedirla.
        if (!empty($datosReserva['fecha']) && !empty($datosReserva['hora_inicio']) && empty($datosReserva['hora_fin']) && empty($datosReserva['duracion'])) {
            $datosReserva['paso_actual'] = 'esperando_hora_fin_o_duracion';
            Cache::put($reservaCacheKey, $datosReserva, now()->addMinutes(self::CACHE_TTL_MINUTES));
            $fechaFormateadaUser = Carbon::parse($datosReserva['fecha'])->locale('es')->isoFormat('D [de] MMMM');
            $horaInicioFormateada = Carbon::parse($datosReserva['hora_inicio'])->format('H:i');
            return ['fulfillmentText' => "Muy bien. Reserva para el {$fechaFormateadaUser} a las {$horaInicioFormateada}. ¿Por cuánto tiempo (ej: 1 hora, 90 minutos) o hasta qué hora (ej: hasta las 5pm, 17:30)?"];
        }

        // ---- Llegamos aquí si tenemos fecha, hora_inicio y (hora_fin o duracion) ----
        // Validar que hora_fin sea posterior a hora_inicio si ambas están presentes
        if (!empty($datosReserva['hora_inicio']) && !empty($datosReserva['hora_fin'])) {
            if (Carbon::parse($datosReserva['hora_fin'])->lte(Carbon::parse($datosReserva['hora_inicio']))) {
                unset($datosReserva['hora_fin']); // Limpiar hora_fin inválida
                unset($datosReserva['duracion']);
                $datosReserva['paso_actual'] = 'esperando_hora_fin_o_duracion';
                Cache::put($reservaCacheKey, $datosReserva, now()->addMinutes(self::CACHE_TTL_MINUTES));
                return ['fulfillmentText' => "La hora de fin debe ser después de la hora de inicio. Por favor, dime de nuevo por cuánto tiempo o hasta qué hora."];
            }
        }


        // 4. Crear/Obtener cliente y verificar si se necesita nombre
        $datosClientePayload = [];
        if (!empty($datosReserva['nombre_cliente_temporal'])) {
            $datosClientePayload['nombre'] = $datosReserva['nombre_cliente_temporal'];
        } else {
            // Si no hay nombre temporal, usar el del perfil de WhatsApp si está disponible
            $nombrePerfilWhatsapp = $parameters['user_profile_name'] ?? null;
            if ($nombrePerfilWhatsapp && strtolower($nombrePerfilWhatsapp) !== 'null' && !is_numeric($nombrePerfilWhatsapp)) {
                $datosClientePayload['nombre_perfil_whatsapp'] = $nombrePerfilWhatsapp;
            }
        }


        $resultadoCliente = $this->clienteService->findOrCreateByTelefono($telefonoCliente, $datosClientePayload);
        $cliente = $resultadoCliente['cliente'];

        if (!$cliente) {
            Cache::forget($reservaCacheKey);
            return ['fulfillmentText' => 'No pudimos identificarte o registrarte en el sistema. Por favor, intenta de nuevo más tarde.'];
        }

        // Si el cliente es nuevo (o no tiene nombre) Y aún no hemos capturado un nombre temporal, pedirlo.
        if (($resultadoCliente['is_new_requiring_data'] || empty($cliente->nombre)) && empty($datosReserva['nombre_cliente_temporal'])) {
            $datosReserva['paso_actual'] = 'esperando_nombre_cliente';
            Cache::put($reservaCacheKey, $datosReserva, now()->addMinutes(self::CACHE_TTL_MINUTES));
            $mensajeNombre = "Para finalizar, necesito tu nombre completo, por favor.";
            if ($resultadoCliente['is_new_requiring_data'] && empty($cliente->nombre)) {
                $mensajeNombre = "Como eres un nuevo cliente, ¿podrías decirme tu nombre completo para registrarte y completar la reserva?";
            }
            return ['fulfillmentText' => $mensajeNombre];
        }

        // Si teníamos un nombre_cliente_temporal, lo usamos para actualizar el cliente si es necesario
        if (!empty($datosReserva['nombre_cliente_temporal']) && $datosReserva['nombre_cliente_temporal'] !== $cliente->nombre) {
            $this->clienteService->actualizarDatosCliente($telefonoCliente, ['nombre' => $datosReserva['nombre_cliente_temporal']]);
            $cliente->refresh(); // Recargar el modelo cliente
        }

        // 5. Proceder a crear la reserva
        Log::info("[RealizarReservaHandler] Todos los datos parecen estar listos. Intentando crear reserva para cliente ID: {$cliente->cliente_id}");

        $resultadoReserva = $this->reservaService->crearReservaEnPrimeraCanchaLibre(
            $cliente->cliente_id,
            $datosReserva['fecha'],
            $datosReserva['hora_inicio'],
            is_array($datosReserva['duracion']) ? $datosReserva['duracion'] : null, // Asegurar que sea array o null
            $datosReserva['hora_fin'],
            $cliente // Pasar el objeto cliente
        );

        if ($resultadoReserva['success']) {
            Cache::forget($reservaCacheKey);
            return ['fulfillmentText' => $resultadoReserva['message']];
        } else {
            // Manejo de errores específicos del servicio de reserva
            $mensajeError = $resultadoReserva['message'];
            if (
                str_contains($mensajeError, 'no hay canchas disponibles') ||
                str_contains($mensajeError, 'fechas pasadas') ||
                str_contains($mensajeError, 'horas pasadas') ||
                str_contains($mensajeError, 'entre las')
            ) {
                // Errores que permiten al usuario reintentar con diferentes datos
                // Resetear solo los datos que causaron el conflicto o pedir que especifique qué cambiar
                $datosReserva['hora_inicio'] = null; // Forzar pedir hora de nuevo
                $datosReserva['hora_fin'] = null;
                $datosReserva['duracion'] = null;
                $datosReserva['paso_actual'] = 'esperando_hora_inicio'; // Volver a pedir la hora para la misma fecha
                Cache::put($reservaCacheKey, $datosReserva, now()->addMinutes(self::CACHE_TTL_MINUTES));
                return ['fulfillmentText' => $mensajeError . "\n¿Quieres intentar con otra hora para el " . Carbon::parse($datosReserva['fecha'])->locale('es')->isoFormat('D [de] MMMM') . " o cambiar la fecha?"];
            }

            // Otros errores más genéricos del servicio, limpiar caché y pedir reintentar
            Cache::forget($reservaCacheKey);
            return ['fulfillmentText' => $mensajeError . " Por favor, intenta de nuevo más tarde o contacta a recepción."];
        }
    }
}