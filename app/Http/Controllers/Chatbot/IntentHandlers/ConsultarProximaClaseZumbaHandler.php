<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ClienteService;
use App\Models\InscripcionClase; // Asumiendo que tienes este modelo
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
        $cliente = $this->clienteService->findClienteByTelefono($senderId);

        if (!$cliente) {
            return $this->prepararRespuesta("No pude encontrarte en el sistema. ¿Ya te has registrado?");
        }

        // Obtener la fecha actual para calcular las fechas de las clases basadas en diaSemana
        $hoy = Carbon::today();
        $proximosSieteDias = [];
        for ($i = 0; $i < 7; $i++) {
            $proximosSieteDias[] = $hoy->copy()->addDays($i);
        }

        $inscripciones = InscripcionClase::where('cliente_id', $cliente->cliente_id)
            ->where('estado', 'Activa') // Solo inscripciones activas
            ->with('claseZumba.instructor', 'claseZumba.area') // Cargar relaciones
            ->get();

        $proximasClasesText = [];

        if ($inscripciones->isEmpty()) {
            return $this->prepararRespuesta("Hola {$cliente->nombre}, no tienes inscripciones próximas a clases de Zumba.");
        }

        $mensaje = "Hola {$cliente->nombre}, estas son tus próximas clases de Zumba inscritas:\n";

        foreach ($inscripciones as $inscripcion) {
            if ($inscripcion->claseZumba) {
                $clase = $inscripcion->claseZumba;
                // Encontrar la próxima ocurrencia de esta clase
                $fechaProximaClase = null;
                foreach ($proximosSieteDias as $dia) {
                    if (ucfirst($dia->dayName) === $clase->diasemama) {
                        $fechaHoraClase = Carbon::parse($dia->toDateString() . ' ' . $clase->hora_inicio->format('H:i:s'));
                        if ($fechaHoraClase->isFuture()) {
                            $fechaProximaClase = $fechaHoraClase;
                            break;
                        }
                    }
                }

                if ($fechaProximaClase) {
                    $proximasClasesText[] = "- ID Clase {$clase->clase_id}: {$clase->diasemama} " . $fechaProximaClase->isoFormat('D MMM') . " de " . $clase->hora_inicio->format('H:i') . " a " . $clase->hora_fin->format('H:i') . " con " . ($clase->instructor->nombre ?? 'instructor asignado') . " (Bs. " . number_format($clase->precio, 2) . ")";
                }
            }
        }

        if (empty($proximasClasesText)) {
            $mensaje = "Hola {$cliente->nombre}, no tienes inscripciones activas a clases de Zumba en los próximos 7 días.";
        } else {
            $mensaje .= implode("\n", $proximasClasesText);
        }


        return $this->prepararRespuesta($mensaje);
    }

    private function prepararRespuesta(string $fulfillmentText): array
    {
        return [
            'messages_to_send' => [
                [
                    'fulfillmentText' => $fulfillmentText,
                    'message_type' => 'text',
                    'payload' => []
                ]
            ],
            'outputContextsToSetActive' => [] // No se necesitan contextos aquí usualmente
        ];
    }
}