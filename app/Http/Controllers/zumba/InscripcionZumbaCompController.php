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

// --- MODELOS AJUSTADOS A TU PROYECTO ---
use App\Models\ClaseZumba;
use App\Models\Instructor;
use App\Models\AreaZumba; // Corregido


class InscripcionZumbaCompController extends Controller
{
    // ... (aquí va todo tu código existente: index, opciones, verComprobante, etc.)
    // ... (no es necesario que lo copies de nuevo, solo asegúrate de que esté aquí)
    
    /**
     * Muestra una lista de comprobantes de pago pendientes de revisión.
     * Cada fila es un comprobante que puede agrupar varias inscripciones.
     */
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
            'area_id'       => 'required|exists:areas_zumba,area_id',
            'instructor_id' => 'required|exists:instructores,instructor_id',
            'diasemama'     => 'required|string',
            'hora_inicio'   => 'required|date_format:H:i',
            'hora_fin'      => 'required|date_format:H:i|after:hora_inicio',
            'precio'        => 'required|numeric|min:0',
            'cupo_maximo'   => 'required|integer|min:1',
        ]);
        
        // Asignamos el estado 'habilitado' por defecto, como en tu modelo
        $validatedData['habilitado'] = true;

        // 2. Creamos la nueva clase en la base de datos
        ClaseZumba::create($validatedData);

        // 3. Redirigimos al usuario con un mensaje de éxito
        return redirect()->route('zumba.opciones')
                       ->with('success', '¡Nuevo horario de clase definido correctamente!');
    }

    // ============= FIN: MÉTODOS AJUSTADOS =============
}