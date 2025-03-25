<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Membresías más utilizadas
        $membresias = DB::table('membresias_cliente as mc')
            ->join('planes_membresia as pm', 'mc.plan_id', '=', 'pm.plan_id')
            ->select('pm.nombre as plan', DB::raw('COUNT(mc.membresia_id) as total'))
            ->groupBy('pm.nombre')
            ->orderBy('total', 'desc')
            ->get();

        // Reservas por mes
        $reservas = DB::table('reservas')
            ->select(DB::raw("DATE_FORMAT(fecha_hora_inicio, '%Y-%m') as mes"), DB::raw('COUNT(*) as total'))
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        // Reservas confirmadas vs canceladas
        $estados = DB::table('reservas')
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->whereIn('estado', ['confirmada', 'cancelada'])
            ->groupBy('estado')
            ->get();

        // Preparar los datos para el view (convertir a arrays)
        $membresias_labels = $membresias->pluck('plan');
        $membresias_data   = $membresias->pluck('total');

        $reservas_labels = $reservas->pluck('mes');
        $reservas_data   = $reservas->pluck('total');

        $estados_labels = $estados->pluck('estado');
        $estados_data   = $estados->pluck('total');

        return view('dashboard', compact(
            'membresias_labels', 'membresias_data',
            'reservas_labels', 'reservas_data',
            'estados_labels', 'estados_data'
        ));
    }
}

