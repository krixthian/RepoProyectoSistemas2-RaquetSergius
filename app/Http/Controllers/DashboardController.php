<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reserva;
use App\Models\InscripcionClase;
use App\Models\Cancha;
use App\Models\ClaseZumba; // Asegúrate de importar el modelo ClaseZumba
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. FILTROS DE FECHA
        $fechaInicio = $request->input('fecha_inicio', Carbon::now()->subMonth()->toDateString());
        $fechaFin = $request->input('fecha_fin', Carbon::now()->toDateString());

        $fechaInicioCarbon = Carbon::parse($fechaInicio)->startOfDay();
        $fechaFinCarbon = Carbon::parse($fechaFin)->endOfDay();

        // --- SECCIÓN DE RESERVAS (WALLY) ---
        $ingresosReservas = Reserva::whereBetween('fecha', [$fechaInicioCarbon, $fechaFinCarbon])
            ->where('pago_completo', true)
            ->sum('monto_total');
        $totalReservas = Reserva::whereBetween('fecha', [$fechaInicioCarbon, $fechaFinCarbon])->count();
        $demandaPorHora = Reserva::whereBetween('fecha', [$fechaInicioCarbon, $fechaFinCarbon])
            ->select(DB::raw('HOUR(hora_inicio) as hora'), DB::raw('count(*) as total'))
            ->groupBy('hora')->orderBy('hora')->pluck('total', 'hora')->all();
        $reservasPorCancha = Cancha::select('nombre')
            ->selectSub(function ($query) use ($fechaInicioCarbon, $fechaFinCarbon) {
                $query->selectRaw('count(*)')->from('reservas')
                    ->whereColumn('reservas.cancha_id', 'canchas.cancha_id')
                    ->whereBetween('fecha', [$fechaInicioCarbon, $fechaFinCarbon]);
            }, 'reservas_count')->get();

        // --- SECCIÓN DE ZUMBA ---
        // Usando la columna 'monto_pagado' que me indicaste.
        // La condición del estado de pago sigue pendiente de tu confirmación.
        $ingresosZumba = InscripcionClase::whereBetween('fecha_clase', [$fechaInicioCarbon, $fechaFinCarbon])
            //->where('NOMBRE_COLUMNA_ESTADO_PAGO', 'VALOR_PAGADO') // <-- Sigue pendiente
            ->sum('monto_pagado');
        $totalInscripciones = InscripcionClase::whereBetween('fecha_clase', [$fechaInicioCarbon, $fechaFinCarbon])->count();

        // **NUEVO: Consulta para el gráfico de demanda de clases de Zumba**
        $inscripcionesPorClase = ClaseZumba::withCount([
            'inscripciones' => function ($query) use ($fechaInicioCarbon, $fechaFinCarbon) {
                $query->whereBetween('fecha_clase', [$fechaInicioCarbon, $fechaFinCarbon]);
            }
        ])->get();
        // **FIN DE LA NUEVA CONSULTA**

        // --- CÁLCULOS TOTALES Y GRÁFICOS ---
        $ingresosTotales = $ingresosReservas + $ingresosZumba;
        $ingresosDiariosData = $this->getIngresosDiarios($fechaInicioCarbon, $fechaFinCarbon);

        $horasDelDia = array_fill(0, 24, 0);
        foreach ($demandaPorHora as $hora => $total) {
            $horasDelDia[$hora] = $total;
        }

        return view('dashboard', compact(
            'fechaInicio',
            'fechaFin',
            'ingresosTotales',
            'ingresosReservas',
            'ingresosZumba',
            'totalReservas',
            'totalInscripciones',
            'horasDelDia',
            'reservasPorCancha',
            'ingresosDiariosData',
            'inscripcionesPorClase' // Se pasa la nueva variable a la vista
        ));
    }

    private function getIngresosDiarios(Carbon $inicio, Carbon $fin)
    {
        $reservas = Reserva::whereBetween('fecha', [$inicio, $fin])
            ->where('pago_completo', true)
            ->groupBy('date')->orderBy('date')
            ->get([DB::raw("DATE(fecha) as date"), DB::raw("SUM(monto_total) as total")])
            ->pluck('total', 'date');

        $zumba = InscripcionClase::whereBetween('fecha_clase', [$inicio, $fin])
            //->where('NOMBRE_COLUMNA_ESTADO_PAGO', 'VALOR_PAGADO') // <-- Sigue pendiente
            ->groupBy('date')->orderBy('date')
            ->get([DB::raw("DATE(fecha_clase) as date"), DB::raw("SUM(monto_pagado) as total")])
            ->pluck('total', 'date');

        $fechas = collect();
        for ($date = $inicio->copy(); $date->lte($fin); $date->addDay()) {
            $formattedDate = $date->toDateString();
            $totalDiario = ($reservas->get($formattedDate) ?? 0) + ($zumba->get($formattedDate) ?? 0);
            $fechas->put($formattedDate, $totalDiario);
        }
        return $fechas;
    }
}