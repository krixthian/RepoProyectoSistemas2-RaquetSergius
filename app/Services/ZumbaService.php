<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\ClaseZumba;
use App\Models\InscripcionClase;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use App\Http\Controllers\Chatbot\IntentHandlers\InscribirClaseZumbaHandler;

class ZumbaService
{
    protected ClienteService $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    /**
     * Inscribe un cliente a una clase de Zumba específica en una fecha dada.
     *
     * @param string $telefonoClienteNormalizado
     * @param int $claseId
     * @param string $fechaClase (YYYY-MM-DD)
     * @param array $datosClienteAdicionales (ej. ['nombre_perfil_whatsapp' => '...'])
     * @return array ['success' => bool, 'message' => string]
     */
    public function inscribirClienteAClasePorId(string $telefonoClienteNormalizado, int $claseId, string $fechaClase, array $datosClienteAdicionales = []): array
    {
        Log::info("[ZumbaService] Intentando inscribir cliente {$telefonoClienteNormalizado} a clase ID {$claseId} para fecha {$fechaClase}");

        $resultadoCliente = $this->clienteService->findOrCreateByTelefono($telefonoClienteNormalizado, $datosClienteAdicionales);
        $cliente = $resultadoCliente['cliente'];

        if (!$cliente) {
            return ['success' => false, 'message' => 'No se pudo identificar o crear tu perfil de cliente.'];
        }
        // Si es nuevo y no se pudo obtener nombre, pedirlo en el handler.
        if ($resultadoCliente['is_new_requiring_data']) {
            return ['success' => false, 'message' => "Necesitamos tu nombre para completar la inscripción. El handler debería pedirlo."];
        }


        $clase = ClaseZumba::find($claseId);
        if (!$clase) {
            return ['success' => false, 'message' => "La clase con ID {$claseId} no existe."];
        }
        if (!$clase->habilitado) {
            return ['success' => false, 'message' => "La clase ID {$claseId} ({$clase->diasemama} {$clase->hora_inicio->format('H:i')}) no está habilitada en este momento."];
        }

        // Validar fecha de la clase (no pasada, no muy lejana)
        try {
            $fechaClaseCarbon = Carbon::parse($fechaClase)->startOfDay();
            $hoy = Carbon::today();
            $fechaLimite = $hoy->copy()->addDays(InscribirClaseZumbaHandler::MAX_DIAS_ANTICIPACION_INSCRIPCION); // Usar constante del handler

            if ($fechaClaseCarbon->isPast() && !$fechaClaseCarbon->isToday()) {
                return ['success' => false, 'message' => "No puedes inscribirte a una clase en una fecha pasada ({$fechaClaseCarbon->isoFormat('D MMM')})."];
            }
            if ($fechaClaseCarbon->gt($fechaLimite)) {
                return ['success' => false, 'message' => "Solo puedes inscribirte con " . InscribirClaseZumbaHandler::MAX_DIAS_ANTICIPACION_INSCRIPCION . " días de anticipación (hasta el {$fechaLimite->isoFormat('D MMM')})."];
            }
            // Validar que la fecha seleccionada corresponda al diaSemama de la clase
            if (ucfirst($fechaClaseCarbon->locale('es_ES')->dayName) !== $clase->diasemama) {
                return ['success' => false, 'message' => "La fecha {$fechaClaseCarbon->isoFormat('D MMM')} no corresponde al día {$clase->diasemama} de la clase ID {$claseId}."];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => "La fecha proporcionada ({$fechaClase}) no es válida."];
        }


        // Validar si ya está inscrito en esa clase y fecha específica
        $yaInscrito = InscripcionClase::where('cliente_id', $cliente->cliente_id)
            ->where('clase_id', $claseId)
            ->where('fecha_clase', $fechaClaseCarbon->toDateString())
            ->where('estado', 'Activa') // Considerar también otros estados si no debe reinscribirse
            ->exists();

        if ($yaInscrito) {
            return ['success' => false, 'message' => "Ya estás inscrito a la clase ID {$claseId} para el {$fechaClaseCarbon->locale('es')->isoFormat('D MMM')}."];
        }

        // Validar cupo (si tienes un campo 'cupo_maximo' y 'inscritos_actuales' en ClaseZumba o AreaZumba)
        // if ($clase->inscritos_actuales >= $clase->cupo_maximo) {
        //    return ['success' => false, 'message' => "Lo sentimos, la clase ID {$claseId} para el {$fechaClaseCarbon->locale('es')->isoFormat('D MMM')} ya no tiene cupos."];
        // }

        try {
            InscripcionClase::create([
                'cliente_id' => $cliente->cliente_id,
                'clase_id' => $claseId,
                'fecha_inscripcion' => Carbon::now(),
                'fecha_clase' => $fechaClaseCarbon->toDateString(),
                'hora_inicio_clase' => $clase->hora_inicio, // Guardar la hora específica
                'monto_pagado' => $clase->precio, // Asumir que se paga el precio de la clase
                'metodo_pago' => 'Chatbot', // O el método que definas
                'estado' => 'Activa', // O 'Pendiente de Pago' si tienes ese flujo
            ]);

            // Opcional: Actualizar contador de inscritos en la clase si lo manejas así
            // $clase->increment('inscritos_actuales');

            Log::info("[ZumbaService] Cliente {$cliente->cliente_id} inscrito a clase ID {$claseId} para fecha {$fechaClaseCarbon->toDateString()}");
            return ['success' => true, 'message' => "¡Inscripción exitosa a la clase ID {$claseId} ({$clase->diasemama} {$clase->hora_inicio->format('H:i')}) para el {$fechaClaseCarbon->locale('es')->isoFormat('D MMM')}!"];
        } catch (Exception $e) {
            Log::error("[ZumbaService] Error al crear inscripción: " . $e->getMessage());
            return ['success' => false, 'message' => "No se pudo completar tu inscripción a la clase ID {$claseId} debido a un error."];
        }
    }

    /**
     * Cancela la inscripción de un cliente a una clase.
     *
     * @param int $clienteId
     * @param int $inscripcionId El ID de la tabla inscripciones_clase
     * @param int $minHorasAnticipacion Mínimo de horas antes para poder cancelar
     * @return array ['success' => bool, 'message' => string]
     */
    public function cancelarInscripcionCliente(int $clienteId, int $inscripcionId, int $minHorasAnticipacion = 2): array
    {
        $inscripcion = InscripcionClase::where('inscripcion_id', $inscripcionId)
            ->where('cliente_id', $clienteId)
            ->where('estado', 'Activa')
            ->first();

        if (!$inscripcion) {
            return ['success' => false, 'message' => "No se encontró una inscripción activa con ID {$inscripcionId} para ti."];
        }

        // Determinar la fecha y hora de inicio de la clase desde la inscripción
        $fechaHoraClase = Carbon::parse($inscripcion->fecha_clase . ' ' . $inscripcion->hora_inicio_clase);

        if ($fechaHoraClase->isPast()) {
            return ['success' => false, 'message' => "No puedes cancelar una clase que ya pasó ({$fechaHoraClase->isoFormat('D MMM H:mm')})."];
        }

        if (Carbon::now()->diffInHours($fechaHoraClase, false) < $minHorasAnticipacion) {
            return ['success' => false, 'message' => "No puedes cancelar la clase ({$fechaHoraClase->isoFormat('D MMM H:mm')}) con menos de {$minHorasAnticipacion} horas de anticipación. Contacta a recepción."];
        }

        try {
            $inscripcion->estado = 'Cancelada';
            $inscripcion->fecha_cancelacion = Carbon::now();
            $inscripcion->save();

            // Opcional: Decrementar contador de inscritos en la clase si lo manejas así
            // if ($inscripcion->claseZumba) {
            //    $inscripcion->claseZumba->decrement('inscritos_actuales');
            // }

            Log::info("[ZumbaService] Inscripción ID {$inscripcionId} para cliente {$clienteId} cancelada.");
            return ['success' => true, 'message' => "Tu inscripción a la clase del {$fechaHoraClase->isoFormat('dddd D MMM [a las] H:mm')} ha sido cancelada."];
        } catch (Exception $e) {
            Log::error("[ZumbaService] Error al cancelar inscripción ID {$inscripcionId}: " . $e->getMessage());
            return ['success' => false, 'message' => "No se pudo cancelar tu inscripción debido a un error."];
        }
    }
}