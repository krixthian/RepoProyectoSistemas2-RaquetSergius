<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Cancha;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
}
