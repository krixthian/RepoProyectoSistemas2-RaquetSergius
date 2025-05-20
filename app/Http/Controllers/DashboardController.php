<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Top 5 clientes que más reservaron
        $topClientes = Cliente::select('clientes.nombre', DB::raw('COUNT(reservas.reserva_id) as total'))
            ->join('reservas', 'clientes.cliente_id', '=', 'reservas.cliente_id')
            ->groupBy('clientes.cliente_id','clientes.nombre')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // 2. Reservas por fecha
        $reservasPorFecha = Reserva::select('fecha', DB::raw('COUNT(*) as total'))
            ->groupBy('fecha')
            ->orderBy('fecha','asc')
            ->get();

        // 3. Estados
        $estados = Reserva::select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->get();

        // 4. Uso de canchas (top 5)
        $usoCanchas = Reserva::select('canchas.nombre', DB::raw('COUNT(reservas.reserva_id) as total'))
            ->join('canchas','reservas.cancha_id','=','canchas.cancha_id')
            ->groupBy('canchas.cancha_id','canchas.nombre')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // — Fallback: si no hay datos, cargamos arreglos de ejemplo
        if ($topClientes->isEmpty()) {
            $topClientesLabels = ['Cliente A','Cliente B','Cliente C','Cliente D','Cliente E'];
            $topClientesData   = [12,9,7,5,3];
        } else {
            $topClientesLabels = $topClientes->pluck('nombre');
            $topClientesData   = $topClientes->pluck('total');
        }

        if ($reservasPorFecha->isEmpty()) {
            $reservasFechaLabels = ['2025-01-01','2025-01-02','2025-01-03','2025-01-04','2025-01-05'];
            $reservasFechaData   = [5,8,6,10,4];
        } else {
            $reservasFechaLabels = $reservasPorFecha->pluck('fecha');
            $reservasFechaData   = $reservasPorFecha->pluck('total');
        }

        if ($estados->isEmpty()) {
            $estadosLabels = ['confirmada','cancelada','pendiente'];
            $estadosData   = [15,4,6];
        } else {
            $estadosLabels = $estados->pluck('estado');
            $estadosData   = $estados->pluck('total');
        }

        if ($usoCanchas->isEmpty()) {
            $usoCanchasLabels = ['Fútbol','Tenis','Padel','Basket','Vóley'];
            $usoCanchasData   = [20,15,10,8,5];
        } else {
            $usoCanchasLabels = $usoCanchas->pluck('nombre');
            $usoCanchasData   = $usoCanchas->pluck('total');
        }

        return view('dashboard', compact(
            'topClientesLabels','topClientesData',
            'reservasFechaLabels','reservasFechaData',
            'estadosLabels','estadosData',
            'usoCanchasLabels','usoCanchasData'
        ));
    }
}
