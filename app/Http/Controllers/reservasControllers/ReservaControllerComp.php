<?php

namespace App\Http\Controllers\reservasControllers;
use App\Http\Controllers\Controller;
use App\Models\Reserva;
use Illuminate\Http\Request;
use App\Models\Cliente;

class ReservaControllerComp extends Controller
{
    public function opciones()
    {
        return view('reservas.opciones');
    }

    public function index(Request $request)
    {
        // Obtener reservas pendientes junto con los datos del cliente relacionado
        $reservasPendientes = Reserva::where('estado', 'Pendiente')
            ->with(['cliente:cliente_id,nombre,telefono'])
            ->orderBy('created_at', 'desc')
            ->get();

        dd($reservasPendientes);
        return view('reservas.pendientes', compact('reservasPendientes'));
    }

    public function verReserva($id_reserva)
    {
        // Obtener la reserva por ID
        $reserva = Reserva::with(['cliente:cliente_id,nombre,telefono'])
            ->findOrFail($id_reserva);

        if ($reserva->estado != 'Pendiente') {
            return redirect()->route('admin.reservas.pendientes')
                ->with('error', 'La reserva no está pendiente.');
        }
        // Verificar si la reserva tiene un cliente asociado
        if ($reserva->cliente) {
            $cliente = $reserva->cliente;
        } else {
            $cliente = null;
        }

        return view('reservas.ver', compact('reserva', 'cliente'));
    }
}
