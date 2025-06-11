<?php

namespace App\Http\Controllers\reservasControllers;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Http\Controllers\Chatbot\whatsappController;

class ReservaControllerComp extends Controller
{
    public function opciones()
    {
        return view('reservas.opciones');
    }

    public function index(Request $request)
    {
        // Obtener solo las reservas pendientes
        $query = Reserva::where('estado', 'Pendiente')->with('cliente');

        // Aplicar filtro de búsqueda si existe
        if ($request->has('cliente_nombre') && $request->cliente_nombre != '') {
            $query->whereHas('cliente', function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->cliente_nombre . '%');
            });
        }

        $reservas = $query->orderBy('created_at', 'desc')->get();

        // Usamos la vista 'index.blade.php' general de reservas
        return view('reservas.revisionPagos.index', compact('reservas'));
    }

    /**
     * Muestra el detalle de una reserva específica.
     * En la vista se podrá ver el comprobante y las acciones.
     */
    public function verReserva($id_reserva)
    {
        $reserva = Reserva::with(['cliente', 'cancha'])->findOrFail($id_reserva);
        return view('reservas.revisionPagos.verReserva', compact('reserva'));
    }

    /**
     * Confirma una reserva pendiente y notifica al cliente.
     */
    public function confirmarReserva(Request $request, $id_reserva)
    {
        // 1. Validar que el mensaje sea obligatorio y tenga al menos 10 caracteres.
        $request->validate([
            'mensaje' => 'required|string|min:10',
        ]);

        $reserva = Reserva::with(['cliente', 'cancha'])->findOrFail($id_reserva);
        $reserva->estado = 'Confirmada';
        $reserva->save();

        // 2. Enviar la notificación con el mensaje del formulario.
        $this->enviarNotificacion(true, $reserva, $request->input('mensaje'));

        return redirect()->route('admin.reservas.pendientes')
            ->with('success', 'La reserva #' . $reserva->reserva_id . ' ha sido CONFIRMADA y se notificó al cliente.');
    }

    /**
     * Rechaza una reserva pendiente y notifica al cliente.
     */
    public function rechazarReserva(Request $request, $id_reserva)
    {
        // 1. Validar que el mensaje sea obligatorio.
        $request->validate([
            'mensaje' => 'required|string|min:10',
        ]);

        $reserva = Reserva::with(['cliente', 'cancha'])->findOrFail($id_reserva);
        $reserva->estado = 'Rechazada';
        $reserva->save();

        // 2. Enviar la notificación con el mensaje del formulario.
        $this->enviarNotificacion(false, $reserva, $request->input('mensaje'));

        return redirect()->route('admin.reservas.pendientes')
            ->with('success', 'La reserva #' . $reserva->reserva_id . ' ha sido RECHAZADA y se notificó al cliente.');
    }


    public function enviarNotificacion(bool $confirmado, $reserva, string $mensajePersonalizado)
    {
        // El cliente y el teléfono se cargaron con with(['cliente'])
        if (!$reserva->cliente || !$reserva->cliente->telefono) { // Asumo que el campo se llama 'telefono'
            \Illuminate\Support\Facades\Log::warning("No se pudo notificar la reserva #{$reserva->reserva_id} porque el cliente no tiene número de teléfono.");
            return;
        }

        // Obtener el número de teléfono en el formato que espera la API (+código de país)
        // Tu método `normalizePhoneNumber` en whatsappController es perfecto para esto,
        // pero como no lo tenemos aquí, asumimos que el número ya está bien guardado.
        // Si no, necesitarías una función helper o servicio para normalizarlo.
        $numero = $reserva->cliente->telefono;
        $nombreCliente = $reserva->cliente->nombre;

        // La fecha ya es un objeto Carbon si lo tienes en los $casts o $dates del modelo Reserva
        $fechaReserva = \Carbon\Carbon::parse($reserva->fecha)->format('d/m/Y');
        $horaInicioReserva = \Carbon\Carbon::parse($reserva->hora_inicio)->format('H:i');
        $horaFinReserva = \Carbon\Carbon::parse($reserva->hora_fin)->format('H:i');

        $detalleReserva = "Cancha: *{$reserva->cancha->nombre}*\nFecha: *{$fechaReserva}*\nHorario: *{$horaInicioReserva} a {$horaFinReserva}*";

        if ($confirmado) {
            $estado = "✅ RESERVA CONFIRMADA ✅";
            $saludo = "¡Hola {$nombreCliente}! Nos complace confirmar tu reserva.";
        } else {
            $estado = "❌ RESERVA RECHAZADA ❌";
            $saludo = "¡Hola {$nombreCliente}! Lamentamos informarte sobre tu solicitud de reserva.";
        }

        $msg = "{$estado}\n\n{$saludo}\n\n*Detalles de la Reserva:*\n{$detalleReserva}\n\n*Mensaje de nuestro equipo:*\n\"{$mensajePersonalizado}\"";

        try {
            // --- INICIO DE LA CORRECCIÓN ---
            // Usa el service container de Laravel para obtener una instancia del controlador.
            // Esto asegura que su constructor se ejecute y todas las dependencias (como los tokens del .env) se carguen.
            $whatsappController = app(whatsappController::class);

            // Llama al método público sendWhatsAppMessage
            // Asegúrate que tu método sendWhatsAppMessage en whatsappController sea público
            $exito = $whatsappController->sendWhatsAppMessage($numero, $msg);

            if ($exito) {
                \Illuminate\Support\Facades\Log::info("Notificación de estado de reserva #{$reserva->reserva_id} enviada a {$numero}.");
            } else {
                \Illuminate\Support\Facades\Log::error("Fallo al enviar notificación de reserva #{$reserva->reserva_id} a {$numero} desde ReservaControllerComp.");
            }
            // --- FIN DE LA CORRECCIÓN ---

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Excepción al intentar enviar WhatsApp desde ReservaControllerComp a {$numero}: " . $e->getMessage());
        }
    }
}