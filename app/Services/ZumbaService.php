<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\ClaseZumba;
use App\Models\InscripcionClase;
use App\Services\ClienteService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ZumbaService
{
    protected ClienteService $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    public function inscribirClienteAClase(
        string $telefonoClienteNormalizado,
        string $diaSemanaEntrada,
        string $horaInicioClase,
        array $datosClienteAdicionales = []
    ): array {
        try {
            $resultadoCliente = $this->clienteService->findOrCreateByTelefono($telefonoClienteNormalizado, $datosClienteAdicionales);
            $cliente = $resultadoCliente['cliente'];
            $isNewRequiringData = $resultadoCliente['is_new_requiring_data'];

            if (!$cliente) {
                return ['success' => false, 'message' => 'No pudimos identificarte o registrarte.'];
            }
            $nombreCliente = $cliente->nombre ?? 'tú';


            $claseZumba = ClaseZumba::where('diasemama', $diaSemanaEntrada)
                ->whereTime('hora_inicio', '=', $horaInicioClase)
                ->where('habilitado', true)
                ->first();

            if (!$claseZumba) {
                $horaDisplay = Carbon::parse($horaInicioClase)->format('H:i');
                return ['success' => false, 'message' => "No encontré una clase de Zumba activa para el {$diaSemanaEntrada} a las {$horaDisplay}. ¿Quieres intentar con otro horario?"];
            }


            $inscripcionesActuales = InscripcionClase::where('clase_id', $claseZumba->clase_id)
                ->where('estado', 'Activa')
                ->count();

            if ($inscripcionesActuales >= $claseZumba->cupo_maximo) {
                return ['success' => false, 'message' => "Lo siento, la clase de Zumba del {$claseZumba->diasemama} a las " . $claseZumba->hora_inicio->format('H:i') . " ya está llena."];
            }

            $yaInscrito = InscripcionClase::where('cliente_id', $cliente->cliente_id)
                ->where('clase_id', $claseZumba->clase_id)
                ->where('estado', 'Activa')
                ->exists();

            if ($yaInscrito) {
                return ['success' => false, 'message' => "Ya te encuentras inscrito/a en la clase de Zumba del {$claseZumba->diasemama} a las " . $claseZumba->hora_inicio->format('H:i') . "."];
            }

            DB::beginTransaction();
            $inscripcion = new InscripcionClase();
            $inscripcion->cliente_id = $cliente->cliente_id;
            $inscripcion->clase_id = $claseZumba->clase_id;
            $inscripcion->fecha_inscripcion = Carbon::now();
            $inscripcion->estado = 'Activa';
            $inscripcion->save();

            //Actualizacion last_activity_at del cliente (CHURN)
            if ($cliente) {
                $cliente->last_activity_at = Carbon::now();
                $cliente->is_churned = false; // Una nueva actividad significa que ya no está en churn
                $cliente->save();
                Log::info("[ZumbaService] Actualizado last_activity_at para cliente ID {$cliente->cliente_id} por inscripción a Zumba.");
            }


            DB::commit();

            $nombreInstructor = $claseZumba->instructor ? $claseZumba->instructor->nombre : 'nuestro instructor';
            $mensajeExito = "¡Perfecto, {$nombreCliente}! Te has inscrito exitosamente a la clase de Zumba del {$claseZumba->diasemama} a las " . $claseZumba->hora_inicio->format('H:i') . " con {$nombreInstructor}.";

            if ($isNewRequiringData) {
                $mensajeExito .= "\n\nNotamos que eres nuevo/a o no tenemos tu nombre completo. Para mejorar tu experiencia, cuando quieras puedes decir 'Menú', luego 'Mis Datos' para actualizar tu información.";
            }

            return [
                'success' => true,
                'message' => $mensajeExito,
                'data' => $inscripcion
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en ZumbaService@inscribirClienteAClase: " . $e->getMessage(), [
                'telefono' => $telefonoClienteNormalizado,
                'dia' => $diaSemanaEntrada,
                'hora' => $horaInicioClase,
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Hubo un problema técnico al procesar tu inscripción. Por favor, intenta nuevamente más tarde.'];
        }
    }
}