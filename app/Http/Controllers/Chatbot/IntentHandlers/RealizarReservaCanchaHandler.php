<?php

// Reemplaza con el namespace correcto según la ubicación del archivo
namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ReservaService;
use App\Services\ClienteService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonInterval;


class RealizarReservaCanchaHandler implements IntentHandlerInterface
{
    // Constantes
    private const HORA_INICIO_OPERACION = 9;
    private const HORA_FIN_OPERACION = 22;
    private const TOTAL_CANCHAS = 3;
    private const MAX_DIAS_ANTICIPACION = 7;

    //services
    protected $reservaService;
    protected $clienteService;

    public function __construct(ReservaService $reservaService, ClienteService $clienteService)
    {
        $this->reservaService = $reservaService;
        $this->clienteService = $clienteService;
    }

    public function handle(array $parameters, string $senderId): string
    {
        Log::info('Executing RealizarReservaCanchaHandler with params: ' . json_encode($parameters));

        // --- 1. Obtener Parámetros ---
        $fechaParam = $parameters['fecha'] ?? null;
        $horaInicioParam = $parameters['horaini'] ?? null;
        $horaFinParam = $parameters['horafin'] ?? null;
        $duracionParam = $parameters['duracion'] ?? null;



        //verificar valores esten vacíos o nulos
        if (empty($fechaParam)) {
            Log::warning('RealizarReservaCanchaHandler: fechaParam missing or empty.');
            return "Por favor, indica la fecha para la reserva.";
        }
        if (empty($horaInicioParam)) {
            Log::warning('RealizarReservaCanchaHandler: horainiParam missing or empty.');

            return "Necesito que me indiques la hora de inicio para la reserva del " . Carbon::parse($fechaParam)->format('d/m/Y') . ".";

        }

        if (empty($horaFinParam) && empty($duracionParam)) {
            Log::warning('RealizarReservaCanchaHandler: horafin Param y duracion Param missing or empty.');
            return "bien! necesito que me indiques cuanto tiempo deseas (ej. 1 hora) o hasta que hora necesitas la reserva (ej. 14:30).";

        }

        // --- 2. Parsear y Determinar Hora Inicio y Fin (con Prioridad) ---
        try {
            $fechaConsulta = Carbon::parse($fechaParam)->startOfDay();
            $fechaString = $fechaConsulta->toDateString();
            $horaInicioSoloTiempo = Carbon::parse($horaInicioParam)->format('H:i:s');
            $fechaHoraInicio = Carbon::parse($fechaString . ' ' . $horaInicioSoloTiempo);
            $fechaHoraFin = null;
            //validacion reserva PROXIMOS 7 DIAS
            $primerDiaPermitido = Carbon::today(); // Desde hoy
            $ultimoDiaPermitido = Carbon::today()->addDays(self::MAX_DIAS_ANTICIPACION - 1)->endOfDay(); // Hoy + 6 días
            if (!$fechaConsulta->between($primerDiaPermitido, $ultimoDiaPermitido)) {
                Log::warning("Reservation attempt outside allowed range ({$fechaConsulta->toDateString()}). Limit: {$ultimoDiaPermitido->toDateString()}");
                return "Lo siento, solo puedes realizar reservas desde hoy hasta los próximos " . self::MAX_DIAS_ANTICIPACION . " días (hasta el " . $ultimoDiaPermitido->format('d/m/Y') . ").";
            }


            // --- Lógica de Prioridad ---
            // Prioridad 1: Usar horafin si es válido
            if ($horaFinParam) {
                try {
                    $horaFinSoloTiempo = Carbon::parse($horaFinParam)->format('H:i:s');
                    $horaFinTemp = Carbon::parse($fechaString . ' ' . $horaFinSoloTiempo);
                    if ($horaFinTemp->gt($fechaHoraInicio)) {
                        $fechaHoraFin = $horaFinTemp; // Establece hora fin usando horafin
                        Log::info("Using 'horafin'. End Time: " . $fechaHoraFin->format('Y-m-d H:i:s'));
                    } else {
                        Log::warning("'horafin' not after 'horaini'.");
                    }
                } catch (\Exception $e) {
                    Log::warning("Cannot parse 'horafin': '{$horaFinParam}'.");
                }
            }

            // Prioridad 2: Usar duracion si horafin NO FUE USADO/VALIDO y duracion es válido
            // $fechaHoraFin === null asegura que solo entremos aquí si horafin no funcionó
            if ($fechaHoraFin === null && $duracionParam && isset($duracionParam['amount'], $duracionParam['unit']) && $duracionParam['amount'] > 0) {
                try {
                    $unit = strtolower($duracionParam['unit']);
                    $amount = $duracionParam['amount'];

                    // --- AJUSTE: Añadir unidades en español ---
                    $interval = match ($unit) {
                        'h', 'hr', 'hour', 'hours', 'hora', 'horas' => CarbonInterval::hours($amount),
                        'min', 'minute', 'minutes', 'minuto', 'minutos' => CarbonInterval::minutes($amount),
                        default => throw new \Exception("Unsupported duration unit: {$unit}"),
                    };
                    // --- FIN AJUSTE ---

                    $finCalculado = $fechaHoraInicio->copy()->add($interval);
                    if ($finCalculado->gt($fechaHoraInicio)) {
                        $fechaHoraFin = $finCalculado;
                        Log::info("Using 'duracion'. End Time: " . $fechaHoraFin->format('Y-m-d H:i:s'));
                    } else {
                        Log::warning("Duration resulted in non-positive time diff.");
                    }
                } catch (\Exception $e) {
                    Log::warning("Cannot parse/use 'duracion': " . json_encode($duracionParam) . ". Error: " . $e->getMessage());
                }
            }


            if ($fechaHoraFin === null) {
                Log::error("Could not determine end time. Check Dialogflow required params & fulfillment settings. Params: " . json_encode($parameters));
                return "No pude determinar la hora de finalización de la reserva. Por favor, intenta indicar la duración o la hora de fin de nuevo.";
            }


            // --- Validaciones Finales ---
            if ($fechaHoraInicio->hour < self::HORA_INICIO_OPERACION || $fechaHoraFin->hour > self::HORA_FIN_OPERACION || ($fechaHoraFin->hour == self::HORA_FIN_OPERACION && $fechaHoraFin->minute > 0)) {
                return "Lo siento, nuestro horario de reservas es de " . sprintf('%02d:00', self::HORA_INICIO_OPERACION) . " a " . sprintf('%02d:00', self::HORA_FIN_OPERACION) . ".";
            }
            if ($fechaConsulta->isPast() && !$fechaConsulta->isToday()) {
                return "No puedes reservar en fechas pasadas.";
            }

        } catch (\Exception $e) {
            // Este catch ahora también puede atrapar el error si $horaInicioParam era ""
            Log::error("Error processing date/time/duration: " . $e->getMessage(), ['params' => $parameters]);
            return "Hubo un error al interpretar la fecha u hora ('{$fechaParam}', '{$horaInicioParam}'). Por favor, verifica que los datos sean correctos.";
        }


        // --- *** Búsqueda de Cliente *** ---
        Log::info("Looking up client for senderId: " . $senderId);
        $cliente = $this->clienteService->findClienteByTelefono($senderId);

        if (!$cliente) {
            Log::warning("Client not found for senderId: " . $senderId);
            // Ajusta este mensaje según tu política (¿registrarse o contactar?)
            return "No te encontré en nuestro sistema. Para reservar, por favor acércate a recepción o regístrate si tenemos esa opción disponible.";
        }
        $clienteId = $cliente->cliente_id;
        Log::info("Client found: ID {$clienteId} ({$cliente->nombre})");
        // --- *** FIN Búsqueda de Cliente *** ---


        // --- *** Verificar Límite de 1 Reserva Futura *** ---
        Log::info("Checking future reservations for client {$clienteId}");
        if ($this->reservaService->clienteTieneReservaFutura($clienteId)) {
            Log::warning("Client {$clienteId} already has an upcoming reservation. Denying new one.");
            // TODO: buscar y mostrar los detalles de la reserva existente si quieres.
            return "Hola {$cliente->nombre}, veo que ya tienes una reserva programada. Solo permitimos una reserva futura activa por cliente. Si deseas cambiarla, por favor cancela la existente primero o contacta a recepción.";
        }
        Log::info("Client {$clienteId} OK for new reservation.");
        // --- *** FIN Verificar Límite *** ---



        // --- 3. Re-Validación de Disponibilidad ---
        $fechaParaServicio = $fechaConsulta->toDateString();
        Log::info("Re-validating availability for {$fechaParaServicio} from {$fechaHoraInicio->format('H:i')} to {$fechaHoraFin->format('H:i')}");
        $reservasDelDia = $this->reservaService->getReservasConfirmadasPorFecha($fechaParaServicio);
        if ($reservasDelDia === null) {
            return "Tuvimos un problema al re-validar la disponibilidad. Intenta de nuevo.";
        }
        $ocupacionPorHora = $this->calculateOcupacionPorHora($reservasDelDia);
        $slotCompletamenteDisponible = true;
        $horaTemp = $fechaHoraInicio->copy();
        while ($horaTemp->lt($fechaHoraFin)) {
            $horaKey = $horaTemp->hour;
            if (!isset($ocupacionPorHora[$horaKey]) || $ocupacionPorHora[$horaKey] >= self::TOTAL_CANCHAS) {
                $slotCompletamenteDisponible = false;
                break;
            }
            $horaTemp->addHour();
        }

        // --- 4. Respuesta o Creación ---
        if (!$slotCompletamenteDisponible) {
            // Slot NO disponible - Mostrar alternativas
            Log::info("Requested slot unavailable ({$fechaHoraInicio->format('H:i')}-{$fechaHoraFin->format('H:i')}). Showing available slots for {$fechaParaServicio}.");
            $horasDisponibles = $this->getHorasDisponibles($ocupacionPorHora);
            $fechaFormateada = $fechaConsulta->format('d/m/Y');

            if (empty($horasDisponibles)) {
                return "Lo siento, el horario que pediste ({$fechaHoraInicio->format('H:i')} - {$fechaHoraFin->format('H:i')}) para el {$fechaFormateada} no está disponible, y parece que no quedan otras horas libres hoy.";
            } else {
                $respuesta = "¡Uy! El horario de {$fechaHoraInicio->format('H:i')} a {$fechaHoraFin->format('H:i')} el {$fechaFormateada} ya no está disponible.\n";
                $respuesta .= "Las horas que aún tienen espacio ese día son:\n";
                $respuesta .= implode("\n", $horasDisponibles);
                $respuesta .= "\n\nPor favor, elige una de estas si deseas reservar.";
                return $respuesta;
            }
        } else {
            // Slot SÍ disponible - Intentar reservar
            Log::info("Requested slot available. Finding cancha and creating reservation.");
            $canchaIdParaReservar = $this->findAvailableCanchaId($reservasDelDia, $fechaHoraInicio, $fechaHoraFin);

            if ($canchaIdParaReservar === null) {
                Log::error("Logic Error: Cannot find available cancha ID for available slot.");
                return "Hubo un problema interno al asignar la cancha. Por favor, contacta a soporte.";
            }

            // --- Lógica de Cliente ---
            // --- *** Búsqueda o Creación de Cliente *** ---
            Log::info("Finding or creating client for senderId: " . $senderId);

            $cliente = $this->clienteService->findOrCreateByTelefono($senderId);

            // Verifica si el servicio pudo encontrar o crear al cliente
            if (!$cliente) {
                Log::error("Failed to find or create client for senderId: " . $senderId . ". Service returned null.");
                // Indica un problema más grave (posiblemente error de BD al crear)
                return "Tuvimos un problema al verificar tu información de cliente. Por favor, intenta de nuevo o contacta a administración.";
            }
            $clienteId = $cliente->cliente_id; // Obtiene el ID (sea existente o nuevo)
            Log::info("Client confirmed/created: ID {$clienteId} ({$cliente->nombre})");
            // --- *** FIN Búsqueda o Creación *** ---

            // --- Lógica de Creación de Reserva ---
            // --- *** Creación REAL de la Reserva *** ---
            Log::info("Attempting to create reservation: Client={$clienteId}, Cancha={$canchaIdParaReservar}, Date={$fechaParaServicio}, Start={$fechaHoraInicio->format('H:i:s')}, End={$fechaHoraFin->format('H:i:s')}");

            // Prepara los datos para el servicio
            // Asegúrate que los nombres de las claves coincidan con $fillable en el Modelo Reserva
            $datosReserva = [
                'cliente_id' => $clienteId,
                'cancha_id' => $canchaIdParaReservar,
                'fecha' => $fechaParaServicio,          // Formato YYYY-MM-DD
                'hora_inicio' => $fechaHoraInicio->format('H:i:s'), // Formato HH:MM:SS
                'hora_fin' => $fechaHoraFin->format('H:i:s'),     // Formato HH:MM:SS
                'estado' => 'Pendiente',                 // Estado inicial
                'monto' => 50.00,                        // !! CALCULAR O OBTENER MONTO REAL !!
                'metodo_pago' => "por confirmar",                   // O 'Por confirmar'
                'pago_completo' => false,
            ];

            // Llama al servicio para crear la reserva
            $nuevaReserva = $this->reservaService->crearReserva($datosReserva);

            if ($nuevaReserva instanceof \App\Models\Reserva) { // Verifica si se creó el objeto Reserva
                $fechaFormateada = $fechaConsulta->format('d/m/Y');
                Log::info("Reservation created successfully. ID: " . $nuevaReserva->reserva_id);
                // Mensaje de éxito
                return "¡Reserva registrada exitosamente, {$cliente->nombre}! Para el {$fechaFormateada} de {$fechaHoraInicio->format('H:i')} a {$fechaHoraFin->format('H:i')} en la cancha #{$canchaIdParaReservar}. Estado: Pendiente. ¿Te puedo ayudar en algo más?";
            } else {
                // Falló la creación en el servicio
                Log::error("Failed creating reservation in service for client {$clienteId}.");
                return "Lo siento, {$cliente->nombre}, ocurrió un error al intentar guardar tu reserva en nuestro sistema. Por favor, inténtalo de nuevo más tarde o contacta a recepción.";
            }
            // --- *** FIN Creación REAL *** ---
        }
    }



    protected function calculateOcupacionPorHora(array $reservasDelDia): array
    {
        $ocupacion = [];
        for ($h = self::HORA_INICIO_OPERACION; $h < self::HORA_FIN_OPERACION; $h++) {
            $ocupacion[$h] = 0;
        }
        if (!empty($reservasDelDia)) {
            foreach ($reservasDelDia as $reserva) {
                try {
                    $inicio = Carbon::parse($reserva['hora_inicio']);
                    $fin = Carbon::parse($reserva['hora_fin']);
                    $actual = $inicio->copy();
                    while ($actual->lt($fin)) {
                        $key = $actual->hour;
                        if (isset($ocupacion[$key])) {
                            $ocupacion[$key]++;
                        }
                        $actual->addHour();
                    }
                } catch (\Exception $e) {
                    Log::warning("Parsing error calculating occupation.");
                }
            }
        }
        return $ocupacion;
    }

    protected function getHorasDisponibles(array $ocupacionPorHora): array
    {
        $disponibles = [];
        for ($h = self::HORA_INICIO_OPERACION; $h < self::HORA_FIN_OPERACION; $h++) {
            if (isset($ocupacionPorHora[$h]) && $ocupacionPorHora[$h] < self::TOTAL_CANCHAS) {
                $disponibles[] = sprintf('%02d:00', $h);
            }
        }
        return $disponibles;
    }

    private function findAvailableCanchaId(array $reservasDelDia, Carbon $inicioSolicitado, Carbon $finSolicitado): ?int
    {
        $canchasOcupadasEnSlot = [];
        foreach ($reservasDelDia as $reserva) {
            try {
                $inicioExistente = Carbon::parse($reserva['hora_inicio']);
                $finExistente = Carbon::parse($reserva['hora_fin']);
                $canchaId = $reserva['cancha_id'];
                if ($inicioSolicitado->lt($finExistente) && $finSolicitado->gt($inicioExistente)) {
                    $canchasOcupadasEnSlot[$canchaId] = true;
                }
            } catch (\Exception $e) {
                Log::warning("Parsing error finding cancha ID.");
            }
        }
        for ($id = 1; $id <= self::TOTAL_CANCHAS; $id++) {
            if (!isset($canchasOcupadasEnSlot[$id])) {
                return $id;
            }
        }
        return null;
    }
}