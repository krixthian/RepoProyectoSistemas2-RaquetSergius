<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Cancha;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\PuntosLog;


class ReservaController extends Controller
{
    public function index(Request $request)
    {
        $query = Reserva::with(['cliente', 'cancha'])
            ->orderBy('fecha', 'desc')
            ->orderBy('hora_inicio', 'desc');

        // Filtro por nombre de cliente
        if ($request->filled('cliente_nombre')) {
            $nombre = $request->cliente_nombre;

            $query->whereHas('cliente', function ($q) use ($nombre) {
                $q->where('nombre', 'like', '%' . $nombre . '%')
                    ->orWhere('apellido', 'like', '%' . $nombre . '%');
            });
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro por pago completo (0 o 1)
        if ($request->filled('pago_completo')) {
            $query->where('pago_completo', $request->pago_completo);
        }

        // Filtro por método de pago
        if ($request->filled('metodo_pago')) {
            $query->where('metodo_pago', 'like', '%' . $request->metodo_pago . '%');
        }

        $reservas = $query->get();

        return view('reservas.index', compact('reservas'));
    }



    public function create()
    {
        $clientes = Cliente::orderBy('nombre')->get();
        $canchas = Cancha::orderBy('nombre')->get();

        return view('reservas.create', compact('clientes', 'canchas'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'cliente_id' => 'required|exists:clientes,cliente_id',
            'cancha_id' => 'required|exists:canchas,cancha_id',
            'fecha' => 'required|date',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'monto' => 'required|numeric|min:0',
            'estado' => 'required|string|max:50',
            'metodo_pago' => 'nullable|string|max:50',
            'pago_completo' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();

            $reserva = Reserva::create($validatedData);

            DB::commit();

            return redirect()->route('reservas.index')
                ->with('success', 'Reserva creada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al crear reserva: " . $e->getMessage());
            return back()->withInput()->withErrors([
                'error_general' => 'Ocurrió un error al guardar la reserva: ' . $e->getMessage()
            ]);
        }
    }

    public function show(Reserva $reserva)
    {
        $reserva->load(['cliente', 'cancha']);
        return view('reservas.show', compact('reserva'));
    }

    public function edit(Reserva $reserva)
    {
        $clientes = Cliente::orderBy('nombre')->get();
        $canchas = Cancha::orderBy('nombre')->get();
        return view('reservas.edit', compact('reserva', 'clientes', 'canchas'));
    }

    public function update(Request $request, Reserva $reserva)
    {
        $validatedData = $request->validate([
            'cliente_id' => 'required|exists:clientes,cliente_id',
            'cancha_id' => 'required|exists:canchas,cancha_id',
            'fecha' => 'required|date',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'monto' => 'required|numeric|min:0',
            'estado' => 'required|string|max:50',
            'metodo_pago' => 'nullable|string|max:50',
            'pago_completo' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();

            $reserva->update($validatedData);

            DB::commit();

            return redirect()->route('reservas.show', $reserva->reserva_id)
                ->with('success', 'Reserva actualizada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al actualizar reserva #{$reserva->reserva_id}: " . $e->getMessage());
            return back()->withInput()->withErrors([
                'error_general' => 'Ocurrió un error al actualizar la reserva: ' . $e->getMessage()
            ]);
        }
    }

    public function destroy(Reserva $reserva)
    {
        try {
            DB::beginTransaction();

            $reserva->delete();

            DB::commit();

            return redirect()->route('reservas.index')
                ->with('success', 'Reserva eliminada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al eliminar reserva #{$reserva->reserva_id}: " . $e->getMessage());
            return redirect()->route('reservas.index')
                ->withErrors(['error_general' => 'No se pudo eliminar la reserva.']);
        }
    }

    /**
     * Muestra una lista de las reservas para el día de hoy.
     */
    public function hoy()
    {
        $reservasHoy = Reserva::with(['cliente', 'cancha'])
            ->whereDate('fecha', Carbon::today())
            ->orderBy('hora_inicio', 'asc')
            ->get();

        return view('reservas.hoy', compact('reservasHoy'));
    }

    /**
     * Muestra el formulario para marcar la asistencia de una reserva.
     */
    public function marcarAsistenciaForm(Reserva $reserva)
    {
        return view('reservas.asistencia', compact('reserva'));
    }

    /**
     * Actualiza el estado de una reserva y asigna puntos si asistió.
     */
    public function actualizarEstado(Request $request, Reserva $reserva)
    {
        $request->validate([
            'estado' => 'required|string|in:Completada,No asistio',
        ]);

        $successMessage = 'El estado de la reserva ha sido actualizado.'; // Mensaje por defecto

        DB::beginTransaction();
        try {
            $reserva->estado = $request->estado;
            $reserva->save();

            // Solo proceder si el estado es 'Completada' y la reserva tiene un cliente asociado
            if ($request->estado === 'Completada' && $reserva->cliente) {

                Log::info("Iniciando cálculo de puntos para reserva #{$reserva->reserva_id} del cliente #{$reserva->cliente_id}.");

                $horaInicio = Carbon::parse($reserva->hora_fin);
                $horaFin = Carbon::parse($reserva->hora_inicio);
                $duracionEnMinutos = $horaFin->diffInMinutes($horaInicio);

                // 5 puntos por cada 30 minutos
                $intervalosDe30Min = floor($duracionEnMinutos / 30);
                $puntosGanados = $intervalosDe30Min * 5;

                Log::info("Reserva ID #{$reserva->reserva_id}: Duración: {$duracionEnMinutos} mins. Intervalos: {$intervalosDe30Min}. Puntos a ganar: {$puntosGanados}.");

                if ($puntosGanados > 0) {
                    $cliente = $reserva->cliente;
                    $puntosAntes = $cliente->puntos;

                    $cliente->increment('puntos', $puntosGanados); // Método más seguro para incrementar

                    // Registrar en el log de puntos
                    PuntosLog::create([
                        'cliente_id' => $cliente->cliente_id,
                        'reserva_id' => $reserva->reserva_id,
                        'accion' => 'Asistencia a Reserva',
                        'puntos_cambio' => $puntosGanados,
                        'puntos_antes' => $puntosAntes,
                        'puntos_despues' => $cliente->puntos + $puntosGanados, // Obtenemos el valor actualizado
                        'detalle' => "Puntos ganados por completar la reserva #{$reserva->reserva_id}",
                        'fecha' => now(),
                    ]);

                    $successMessage = "Estado actualizado. Se asignaron {$puntosGanados} puntos a {$cliente->nombre}.";
                    Log::info("Puntos asignados correctamente al cliente #{$cliente->cliente_id}.");

                    // Lógica para enviar notificación por WhatsApp
                    if ($cliente->telefono) {
                        $whatsappMessage = "¡Gracias por tu asistencia, {$cliente->nombre}! Has ganado {$puntosGanados} puntos por tu reserva en Raquet Sergius. Tu nuevo saldo es de " . ($cliente->puntos + $puntosGanados) . " puntos. ¡Sigue así!";

                        try {
                            Log::info("Intentando enviar notificación de puntos a {$cliente->telefono}.");
                            app('App\Http\Controllers\Chatbot\whatsappController')->sendWhatsAppMessage($cliente->telefono, $whatsappMessage);
                            Log::info("Llamada al método sendWhatsAppMessage realizada para {$cliente->telefono}.");
                            $successMessage .= " Notificación enviada.";
                        } catch (\Exception $e) {
                            Log::error("Fallo al enviar notificación de puntos por WhatsApp al cliente {$cliente->cliente_id}: " . $e->getMessage());
                            // No fallamos la transacción, solo registramos el error
                            $successMessage .= " La notificación por WhatsApp no pudo ser enviada.";
                        }
                    } else {
                        Log::warning("El cliente #{$cliente->cliente_id} no tiene un número de teléfono para notificar.");
                    }
                } else {
                    $successMessage = 'Estado actualizado. No se asignaron puntos (duración menor a 30 min).';
                }
            }

            DB::commit();
            Log::info("Transacción para reserva #{$reserva->reserva_id} completada (commit).");
            return redirect()->route('reservas.hoy')->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::critical("Error CRÍTICO al actualizar estado/puntos de reserva #{$reserva->reserva_id}, transacción revertida. Error: " . $e->getMessage());
            return redirect()->route('reservas.hoy')->with('error', 'Ocurrió un error grave al procesar la asistencia.');
        }
    }
}
