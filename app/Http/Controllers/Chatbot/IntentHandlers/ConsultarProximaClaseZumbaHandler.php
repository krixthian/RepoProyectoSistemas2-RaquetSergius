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
        $cliente = $this->clienteService->findClienteByTelefono($senderId);

        if (!$cliente) {
            return $this->prepararRespuesta("No pude encontrarte en el sistema.");
        }

        $hoy = Carbon::today();
        $inscripciones = InscripcionClase::where('cliente_id', $cliente->cliente_id)
            ->whereIn('estado', ['Activa', 'Pendiente']) // Mostrar ambos estados
            ->where('fecha_clase', '>=', $hoy->toDateString())
            ->with(['claseZumba.instructor', 'claseZumba.area'])
            ->orderBy('fecha_clase', 'asc')
            ->orderByRaw('TIME((SELECT hora_inicio FROM clases_zumba WHERE clases_zumba.clase_id = inscripciones_clase.clase_id)) asc')
            ->take(5)
            ->get();

        if ($inscripciones->isEmpty()) {
            return $this->prepararRespuesta("Hola {$cliente->nombre}, no tienes inscripciones próximas a clases de Zumba.");
        }

        $mensaje = "Hola {$cliente->nombre}, estas son tus próximas inscripciones a clases de Zumba:\n";
        foreach ($inscripciones as $inscripcion) {
            $clase = $inscripcion->claseZumba;
            if ($clase) {
                $fechaClase = Carbon::parse($inscripcion->fecha_clase)->isoFormat('dddd D [de] MMMM');
                $horaInicio = Carbon::parse($clase->hora_inicio)->format('H:i');
                $precioClase = $clase->precio ?? $inscripcion->monto_pagado ?? 'N/D';
                $estadoInscripcion = $inscripcion->estado === 'Activa' ? '✅ Confirmada' : '⏳ Pendiente de Pago';

                $mensaje .= "\n- Para el *{$fechaClase}* a las {$horaInicio}\n";
                $mensaje .= "  Clase: ID {$clase->clase_id} con " . ($clase->instructor->nombre ?? 'N/A') . "\n";
                $mensaje .= "  Precio: Bs. " . number_format((float) $precioClase, 2) . "\n";
                $mensaje .= "  Estado: *{$estadoInscripcion}*\n";
                $mensaje .= "  (ID Inscripción para cancelar: {$inscripcion->inscripcion_id})\n";
            }
        }

        return $this->prepararRespuesta($mensaje);
    }
    private function prepararRespuesta(string|array $fulfillmentTextOrMessages, array $outputContextsToSetActive = [], string $messageType = 'text', array $payload = []): array
    {
        $messages = [];
        if (is_string($fulfillmentTextOrMessages)) {
            $messages[] = ['fulfillmentText' => $fulfillmentTextOrMessages, 'message_type' => $messageType, 'payload' => $payload];
        } elseif (is_array($fulfillmentTextOrMessages) && !empty($fulfillmentTextOrMessages) && isset($fulfillmentTextOrMessages[0]['fulfillmentText'])) {
            $messages = $fulfillmentTextOrMessages; // Asume que ya es un array de mensajes
        } else { // Fallback si la estructura no es la esperada
            $messages[] = ['fulfillmentText' => "Se produjo un error al preparar la respuesta.", 'message_type' => 'text', 'payload' => []];
        }
        return ['messages_to_send' => $messages, 'outputContextsToSetActive' => $outputContextsToSetActive];
    }
}