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

class InscripcionZumbaCompController extends Controller
{
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
            // --- CORRECCIÓN AQUÍ ---
            // Ordenar por la fecha de inscripción más antigua del grupo para atender primero a los que llevan más tiempo esperando.
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
        // Decodificar la ruta del comprobante desde el hash de la URL
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

    /**
     * Confirma un grupo de inscripciones pendientes y notifica al cliente.
     */
    public function confirmarInscripciones(Request $request, $cliente_id, $comprobante_hash)
    {
        $request->validate(['mensaje' => 'required|string|min:10']);
        $this->actualizarEstadoInscripciones($cliente_id, $comprobante_hash, 'Activa', $request->input('mensaje'));

        return redirect()->route('zumba.pendientes')
            ->with('success', 'Inscripciones CONFIRMADAS y cliente notificado.');
    }

    /**
     * Rechaza un grupo de inscripciones pendientes y notifica al cliente.
     */
    public function rechazarInscripciones(Request $request, $cliente_id, $comprobante_hash)
    {
        $request->validate(['mensaje' => 'required|string|min:10']);
        $this->actualizarEstadoInscripciones($cliente_id, $comprobante_hash, 'Rechazada', $request->input('mensaje'));

        return redirect()->route('zumba.pendientes')
            ->with('success', 'Inscripciones RECHAZADAS y cliente notificado.');
    }

    /**
     * Lógica central para actualizar estado y enviar notificación.
     */
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
                // Si el estado es Activa y no tiene fecha de pago, se la ponemos ahora
                if ($nuevo_estado === 'Activa' && is_null($inscripcion->fecha_pago)) {
                    $inscripcion->fecha_pago = Carbon::now();
                }
                $inscripcion->save();
            }
        });

        $this->enviarNotificacion($nuevo_estado === 'Activa', $inscripciones, $mensajePersonalizado);
    }

    /**
     * Envía una notificación por WhatsApp al cliente.
     */
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
}