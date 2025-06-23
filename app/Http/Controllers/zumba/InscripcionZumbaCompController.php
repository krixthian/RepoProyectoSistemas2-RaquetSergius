<?php

namespace App\Http\Controllers\zumba;

use App\Http\Controllers\Controller;
use App\Models\InscripcionClase;
use Illuminate\Http\Request;
use App\Http\Controllers\Chatbot\whatsappController;
use App\Models\Cliente;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\PuntosLog; // Asegúrate de que este modelo exista

// --- MODELOS AJUSTADOS A TU PROYECTO ---
use App\Models\ClaseZumba;
use App\Models\Instructor;
use App\Models\AreaZumba; // Corregido


class InscripcionZumbaCompController extends Controller
{

    public function index(Request $request)
    {
        $query = InscripcionClase::where('estado', 'Pendiente')
            ->whereNotNull('ruta_comprobante_pago')
            ->with('cliente')
            ->select('cliente_id', 'ruta_comprobante_pago', DB::raw('count(*) as total_clases'), DB::raw('sum(monto_pagado) as monto_total'), DB::raw('MIN(fecha_inscripcion) as fecha_primera_inscripcion'))
            ->groupBy('cliente_id', 'ruta_comprobante_pago')
            ->orderBy('fecha_primera_inscripcion', 'asc');

        if ($request->has('cliente_nombre') && $request->cliente_nombre != '') {
            $query->whereHas('cliente', function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->cliente_nombre . '%');
            });
        }

        $comprobantes = $query->get();

        return view('zumba.revisionPagos.index', compact('comprobantes'));
    }
    public function opciones()
    {

        return view('zumba.opciones');
    }

    /**
     * Muestra el detalle de un grupo de inscripciones asociadas a un comprobante.
     */
    public function verComprobante($cliente_id, $comprobante_hash)
    {
        $ruta_comprobante_pago = str_replace('-', '/', $comprobante_hash);

        $inscripciones = InscripcionClase::where('cliente_id', $cliente_id)
            ->where('ruta_comprobante_pago', $ruta_comprobante_pago)
            ->where('estado', 'Pendiente')
            ->with(['cliente', 'claseZumba.instructor'])
            ->get();

        if ($inscripciones->isEmpty()) {
            abort(404, 'No se encontraron inscripciones pendientes para este comprobante.');
        }

        return view('zumba.revisionPagos.verComprobante', compact('inscripciones'));
    }

    public function confirmarInscripciones(Request $request, $cliente_id, $comprobante_hash)
    {
        $request->validate(['mensaje' => 'required|string|min:10']);
        $this->actualizarEstadoInscripciones($cliente_id, $comprobante_hash, 'Activa', $request->input('mensaje'));

        return redirect()->route('zumba.pendientes')
            ->with('success', 'Inscripciones CONFIRMADAS y cliente notificado.');
    }

    public function rechazarInscripciones(Request $request, $cliente_id, $comprobante_hash)
    {
        $request->validate(['mensaje' => 'required|string|min:10']);
        $this->actualizarEstadoInscripciones($cliente_id, $comprobante_hash, 'Rechazada', $request->input('mensaje'));

        return redirect()->route('zumba.pendientes')
            ->with('success', 'Inscripciones RECHAZADAS y cliente notificado.');
    }

    private function actualizarEstadoInscripciones($cliente_id, $comprobante_hash, $nuevo_estado, $mensajePersonalizado)
    {
        $ruta_comprobante_pago = str_replace('-', '/', $comprobante_hash);
        $inscripciones = InscripcionClase::where('cliente_id', $cliente_id)
            ->where('ruta_comprobante_pago', $ruta_comprobante_pago)
            ->where('estado', 'Pendiente')
            ->with(['cliente', 'claseZumba'])
            ->get();

        if ($inscripciones->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($inscripciones, $nuevo_estado) {
            foreach ($inscripciones as $inscripcion) {
                $inscripcion->estado = $nuevo_estado;
                if ($nuevo_estado === 'Activa' && is_null($inscripcion->fecha_pago)) {
                    $inscripcion->fecha_pago = Carbon::now();
                }
                $inscripcion->save();
            }
        });

        $this->enviarNotificacion($nuevo_estado === 'Activa', $inscripciones, $mensajePersonalizado);
    }

    public function enviarNotificacion(bool $confirmado, $inscripciones, string $mensajePersonalizado)
    {
        $primeraInscripcion = $inscripciones->first();
        if (!$primeraInscripcion || !$primeraInscripcion->cliente || !$primeraInscripcion->cliente->telefono) {
            Log::warning("No se pudo notificar la inscripción de zumba porque el cliente no tiene número de teléfono.");
            return;
        }

        $numero = $primeraInscripcion->cliente->telefono;
        $nombreCliente = $primeraInscripcion->cliente->nombre;
        $montoTotal = $inscripciones->sum('monto_pagado');

        $detallesClases = "";
        foreach ($inscripciones as $insc) {
            if ($insc->claseZumba) {
                $fechaClase = Carbon::parse($insc->fecha_clase)->isoFormat('dddd D MMM');
                $horaInicio = Carbon::parse($insc->claseZumba->hora_inicio)->format('H:i');
                $detallesClases .= "- Clase ID {$insc->clase_id} el *{$fechaClase}* a las {$horaInicio}\n";
            }
        }

        if ($confirmado) {
            $estado = "✅ INSCRIPCIONES CONFIRMADAS ✅";
            $saludo = "¡Hola {$nombreCliente}! Te confirmamos tus inscripciones a las clases de Zumba.";
        } else {
            $estado = "❌ INSCRIPCIONES RECHAZADAS ❌";
            $saludo = "¡Hola {$nombreCliente}! Lamentamos informarte sobre tu solicitud de inscripción.";
        }

        $msg = "{$estado}\n\n{$saludo}\n\n*Clases correspondientes al pago de Bs. " . number_format($montoTotal, 2) . ":*\n{$detallesClases}\n*Mensaje de nuestro equipo:*\n\"{$mensajePersonalizado}\"";

        try {
            $whatsappController = app(whatsappController::class);
            $exito = $whatsappController->sendWhatsAppMessage($numero, $msg);
            if ($exito) {
                Log::info("Notificación de estado de inscripción de Zumba enviada a {$numero}.");
            } else {
                Log::error("Fallo al enviar notificación de inscripción de Zumba a {$numero}.");
            }
        } catch (\Exception $e) {
            Log::error("Excepción al intentar enviar WhatsApp desde InscripcionZumbaCompController: " . $e->getMessage());
        }
    }


    // ============= INICIO: MÉTODOS AJUSTADOS PARA AGENDAR CLASES =============

    /**
     * Muestra el formulario para definir un nuevo horario de clase de Zumba.
     */
    public function showAgendarForm()
    {
        // Obtenemos los datos necesarios para los menús desplegables del formulario
        $instructores = Instructor::all();
        // Usamos tu modelo AreaZumba y filtramos por las que están disponibles
        $areas = AreaZumba::where('disponible', true)->get();

        $diasDeLaSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        // Retornamos la vista y le pasamos los datos
        return view('zumba.agendar', compact('instructores', 'areas', 'diasDeLaSemana'));
    }

    /**
     * Guarda en la base de datos el nuevo horario de clase.
     */
    public function storeAgendar(Request $request)
    {
        // 1. Validamos los datos del formulario (ajustado a tu modelo ClaseZumba)
        $validatedData = $request->validate([
            'area_id' => 'required|exists:areas_zumba,area_id',
            'instructor_id' => 'required|exists:instructores,instructor_id',
            'diasemama' => 'required|string',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'precio' => 'required|numeric|min:0',
            'cupo_maximo' => 'required|integer|min:1',
        ]);

        // Asignamos el estado 'habilitado' por defecto, como en tu modelo
        $validatedData['habilitado'] = true;

        // 2. Creamos la nueva clase en la base de datos
        ClaseZumba::create($validatedData);

        // 3. Redirigimos al usuario con un mensaje de éxito
        return redirect()->route('zumba.opciones')
            ->with('success', '¡Nuevo horario de clase definido correctamente!');
    }

    public function hoy()
    {
        $inscripcionesHoy = InscripcionClase::with(['cliente', 'claseZumba.instructor'])
            ->whereDate('fecha_clase', Carbon::today())
            ->orderBy('fecha_inscripcion', 'desc')
            ->get();

        return view('zumba.hoy', compact('inscripcionesHoy'));
    }

    /**
     * Muestra el formulario para marcar la asistencia de una inscripción.
     */
    public function marcarAsistenciaForm(InscripcionClase $inscripcion)
    {
        // Cargar relaciones para mostrar detalles completos
        $inscripcion->load(['cliente', 'claseZumba.instructor', 'claseZumba.area']);
        return view('zumba.asistencia', compact('inscripcion'));
    }

    /**
     * Actualiza el estado de una inscripción y asigna puntos si asistió.
     */
    public function actualizarEstado(Request $request, InscripcionClase $inscripcion)
    {
        $request->validate([
            'estado' => 'required|string|in:Asistió,No Asistió',
        ]);

        // NOTA: Asumiré que quieres guardar este nuevo estado en la columna 'estado_pago'
        // Si tienes otra columna como 'estado_asistencia', cambia 'estado_pago' por ese nombre.
        $columnaEstado = 'estado_pago';

        $successMessage = 'El estado de la inscripción ha sido actualizado.';

        DB::beginTransaction();
        try {
            $inscripcion->update([$columnaEstado => $request->estado]);

            if ($request->estado === 'Asistió' && $inscripcion->cliente) {
                $puntosGanados = 5; // 5 puntos fijos por clase asistida
                $cliente = $inscripcion->cliente;
                $puntosAntes = $cliente->puntos;

                $cliente->increment('puntos', $puntosGanados);

                PuntosLog::create([
                    'cliente_id' => $cliente->cliente_id,
                    'inscripcion_clase_id' => $inscripcion->inscripcion_id, // Columna correcta para el log
                    'accion' => 'Asistencia a Clase de Zumba',
                    'puntos_cambio' => $puntosGanados,
                    'puntos_antes' => $puntosAntes,
                    'puntos_despues' => $cliente->fresh()->puntos, // Usar fresh() para obtener el valor actualizado
                    'detalle' => "Puntos ganados por asistir a la clase de zumba",
                    'fecha' => now(),
                ]);

                $successMessage = "Estado actualizado. Se asignaron {$puntosGanados} puntos a {$cliente->nombre}.";

                // Enviar notificación por WhatsApp
                if ($cliente->telefono) {
                    $whatsappMessage = "¡Gracias por asistir a tu clase de Zumba, {$cliente->nombre}! Has ganado {$puntosGanados} puntos de fidelidad. Tu saldo actual es de " . ($cliente->fresh()->puntos) . " puntos.";
                    try {
                        app('App\Http\Controllers\Chatbot\whatsappController')->sendWhatsAppMessage($cliente->telefono, $whatsappMessage);
                        $successMessage .= " Notificación enviada.";
                    } catch (\Exception $e) {
                        Log::error("Fallo al enviar notificación de puntos (Zumba) al cliente {$cliente->cliente_id}: " . $e->getMessage());
                        $successMessage .= " La notificación por WhatsApp no pudo ser enviada.";
                    }
                }
            }

            DB::commit();
            return redirect()->route('zumba.asistencia.hoy')->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::critical("Error CRÍTICO al actualizar estado/puntos de inscripción #{$inscripcion->inscripcion_id}: " . $e->getMessage());
            return redirect()->route('zumba.asistencia.hoy')->with('error', 'Ocurrió un error grave al procesar la asistencia.');
        }
    }
}