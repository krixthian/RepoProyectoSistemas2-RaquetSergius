<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ClienteService;
use App\Models\InscripcionClase;
use App\Models\ClaseZumba;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ConsultarProximaClaseZumbaHandler implements IntentHandlerInterface
{
    protected ClienteService $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    public function handle(array $parameters, string $senderId, ?string $action = null): array
    {
        Log::info("[ConsultarProximaClaseZumbaHandler] Executing for senderId: {$senderId}");
        Carbon::setLocale('es');
        $cliente = $this->clienteService->findClienteByTelefono($senderId); // senderId ya normalizado

        $messages = [];
        $outputContextsToSetActive = [];

        if (!$cliente) {
            $messages[] = ['fulfillmentText' => "No pude encontrarte en el sistema. ¿Ya te has registrado o inscrito a alguna clase?", 'message_type' => 'text', 'payload' => []];
        } else {
            $hoy = Carbon::today();
            $proximosSieteDiasConFechaYDia = [];
            for ($i = 0; $i < 7; $i++) {
                $dia = $hoy->copy()->addDays($i);
                $proximosSieteDiasConFechaYDia[ucfirst($dia->locale('es_ES')->dayName)] = $dia->toDateString();
            }

            // Obtener las inscripciones activas del cliente
            $inscripciones = InscripcionClase::where('cliente_id', $cliente->cliente_id)
                ->where('estado', 'Activa')
                ->with(['claseZumba.instructor', 'claseZumba.area'])
                ->get();

            $proximasClasesTextArray = [];

            if ($inscripciones->isEmpty()) {
                $messages[] = ['fulfillmentText' => "Hola {$cliente->nombre}, no tienes inscripciones activas a clases de Zumba.", 'message_type' => 'text', 'payload' => []];
            } else {
                $mensaje = "Hola {$cliente->nombre}, estas son tus próximas clases de Zumba inscritas (en los próximos 7 días):\n";
                foreach ($inscripciones as $inscripcion) {
                    $clase = $inscripcion->claseZumba;
                    if ($clase && isset($proximosSieteDiasConFechaYDia[$clase->diasemama])) {
                        $fechaEspecificaClase = $proximosSieteDiasConFechaYDia[$clase->diasemama];
                        $fechaHoraClase = Carbon::parse($fechaEspecificaClase . ' ' . $clase->hora_inicio->format('H:i:s'));

                        // Solo mostrar si es hoy más tarde o en el futuro dentro de los 7 días
                        if ($fechaHoraClase->isFuture()) {
                            $fechaFormateada = $fechaHoraClase->isoFormat('dddd D [de] MMMM');
                            $horaInicio = $clase->hora_inicio->format('H:i');
                            $horaFin = $clase->hora_fin->format('H:i');
                            $instructorNombre = $clase->instructor->nombre ?? 'N/A';
                            $areaNombre = $clase->area->nombre ?? 'N/A';
                            $precioClase = $clase->precio ?? 'N/D';

                            $proximasClasesTextArray[] =
                                "\n- Para el *{$fechaFormateada}*\n" .
                                "  Clase ID {$clase->clase_id} de {$horaInicio} a {$horaFin}\n" .
                                "  Instructor: {$instructorNombre}\n" .
                                "  Lugar: {$areaNombre}\n" .
                                "  Precio: Bs. " . number_format((float) $precioClase, 2) . "\n" .
                                "  (ID Inscripción para cancelar: *{$inscripcion->inscripcion_id}*)";
                        }
                    }
                }

                if (empty($proximasClasesTextArray)) {
                    $mensaje = "Hola {$cliente->nombre}, no tienes inscripciones activas a clases de Zumba en los próximos 7 días.";
                } else {
                    $mensaje .= implode("\n", $proximasClasesTextArray);
                }
                $messages[] = ['fulfillmentText' => $mensaje, 'message_type' => 'text', 'payload' => []];
            }
        }

        return [
            'messages_to_send' => $messages,
            'outputContextsToSetActive' => $outputContextsToSetActive
        ];
    }
}