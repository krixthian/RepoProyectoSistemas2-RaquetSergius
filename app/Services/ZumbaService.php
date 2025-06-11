<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\ClaseZumba;
use App\Models\InscripcionClase;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use App\Http\Controllers\Chatbot\IntentHandlers\InscribirClaseZumbaHandler;
use Illuminate\Support\Facades\Schema;

class ZumbaService
{
    protected ClienteService $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    /**
     * Inscribe un cliente a una clase de Zumba en una fecha específica, creando el registro como 'Pendiente'.
     */
    public function inscribirClienteAClasePorId(string $telefonoClienteNormalizado, int $claseId, string $fechaClase, array $datosClienteAdicionales = []): array
    {
        Log::info("[ZumbaService] Intentando inscribir cliente {$telefonoClienteNormalizado} a clase ID {$claseId} para fecha {$fechaClase}");

        $resultadoCliente = $this->clienteService->findOrCreateByTelefono($telefonoClienteNormalizado, $datosClienteAdicionales);
        $cliente = $resultadoCliente['cliente'];
        if (!$cliente) {
            return ['success' => false, 'message' => 'No se pudo identificar o crear tu perfil de cliente.'];
        }

        $clase = ClaseZumba::find($claseId);
        if (!$clase) {
            return ['success' => false, 'message' => "La clase con ID {$claseId} no existe."];
        }
        if (!$clase->habilitado) {
            return ['success' => false, 'message' => "La clase ID {$claseId} ({$clase->diasemama} {$clase->hora_inicio->format('H:i')}) no está habilitada."];
        }

        try {
            $fechaClaseCarbon = Carbon::parse($fechaClase)->startOfDay();
            if (ucfirst($fechaClaseCarbon->locale('es_ES')->dayName) !== $clase->diasemama) {
                return ['success' => false, 'message' => "Error interno: La fecha {$fechaClaseCarbon->isoFormat('D MMM')} no corresponde al día {$clase->diasemama} de la clase ID {$claseId}."];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => "La fecha de clase proporcionada ({$fechaClase}) no es válida."];
        }

        $yaInscrito = InscripcionClase::where('cliente_id', $cliente->cliente_id)
            ->where('clase_id', $claseId)
            ->where('fecha_clase', $fechaClaseCarbon->toDateString())
            ->whereIn('estado', ['Activa', 'Pendiente']) // Ya está inscrito o pendiente
            ->exists();

        if ($yaInscrito) {
            return ['success' => false, 'message' => "Ya tienes una inscripción (activa o pendiente) para la clase ID {$claseId} del {$fechaClaseCarbon->locale('es')->isoFormat('D MMM')}."];
        }

        try {
            $inscripcion = InscripcionClase::create([
                'cliente_id' => $cliente->cliente_id,
                'clase_id' => $claseId,
                'fecha_inscripcion' => Carbon::now(),
                'fecha_clase' => $fechaClaseCarbon->toDateString(),
                'estado' => 'Pendiente', // <--- ESTADO INICIAL
                'monto_pagado' => $clase->precio, // Guardar el precio de la clase
            ]);

            Log::info("[ZumbaService] Cliente {$cliente->cliente_id} pre-inscrito (ID: {$inscripcion->inscripcion_id}) a clase ID {$claseId} para fecha {$fechaClaseCarbon->toDateString()}");
            return [
                'success' => true,
                'message' => "Pre-inscripción a la clase ID {$claseId} ({$clase->diasemama} {$clase->hora_inicio->format('H:i')}) para el {$fechaClaseCarbon->locale('es')->isoFormat('D MMM')} registrada.",
                'inscripcion' => $inscripcion // Devolver para obtener datos si es necesario
            ];
        } catch (Exception $e) {
            Log::error("[ZumbaService] Error al crear inscripción PENDIENTE: " . $e->getMessage());
            return ['success' => false, 'message' => "No se pudo registrar tu solicitud para la clase ID {$claseId}."];
        }
    }

    /**
     * Cancela la inscripción de un cliente a una clase.
     */
    public function cancelarInscripcionCliente(int $clienteId, int $inscripcionId, int $minHorasAnticipacion = 2): array
    {
        $inscripcion = InscripcionClase::where('inscripcion_id', $inscripcionId)
            ->where('cliente_id', $clienteId)
            ->whereIn('estado', ['Activa', 'Pendiente']) // Se pueden cancelar ambas
            ->with('claseZumba')
            ->first();

        if (!$inscripcion) {
            return ['success' => false, 'message' => "No se encontró una inscripción activa o pendiente con ID {$inscripcionId}."];
        }
        if (!$inscripcion->claseZumba) {
            return ['success' => false, 'message' => "No se pudo obtener la información de la clase para la inscripción ID {$inscripcionId}."];
        }

        $fechaHoraClase = Carbon::parse($inscripcion->fecha_clase . ' ' . $inscripcion->claseZumba->hora_inicio->format('H:i:s'));

        if ($fechaHoraClase->isPast()) {
            return ['success' => false, 'message' => "No puedes cancelar una clase que ya pasó."];
        }
        if (Carbon::now()->diffInHours($fechaHoraClase, false) < $minHorasAnticipacion) {
            return ['success' => false, 'message' => "No puedes cancelar con menos de {$minHorasAnticipacion} horas de anticipación. Contacta a recepción."];
        }

        try {
            $inscripcion->estado = 'Cancelada';
            // Asumiendo que tienes una columna fecha_cancelacion nullable
            if (Schema::hasColumn('inscripciones_clase', 'fecha_cancelacion')) {
                $inscripcion->fecha_cancelacion = Carbon::now();
            }
            $inscripcion->save();

            Log::info("[ZumbaService] Inscripción ID {$inscripcionId} para cliente {$clienteId} cancelada.");
            return ['success' => true, 'message' => "Tu inscripción a la clase del {$fechaHoraClase->isoFormat('dddd D MMM [a las] H:mm')} ha sido cancelada."];
        } catch (Exception $e) {
            Log::error("[ZumbaService] Error al cancelar inscripción ID {$inscripcionId}: " . $e->getMessage());
            return ['success' => false, 'message' => "No se pudo cancelar tu inscripción debido a un error."];
        }
    }
}