<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cliente;
use Carbon\Carbon;
use Carbon\CarbonPeriod; // Para iterar sobre meses
use Illuminate\Support\Facades\Log;

class ChurnController extends Controller
{
    /**
     * Calcula el número de clientes al inicio del periodo dado que no estaban en churn.
     */
    private function getClientesActivosAlInicio(Carbon $fechaInicioPeriodo, int $churnDefinitionMonths): int
    {
        return Cliente::where('created_at', '<', $fechaInicioPeriodo)
            ->where(function ($query) use ($fechaInicioPeriodo, $churnDefinitionMonths) {
                $query->whereRaw(
                    "DATE_ADD(IFNULL(last_activity_at, created_at), INTERVAL ? MONTH) >= ?",
                    [$churnDefinitionMonths, $fechaInicioPeriodo->toDateString()]
                );
            })
            ->count();
    }

    /**
     * Calcula el número de clientes que entraron en churn durante el sub-periodo dado.
     */
    private function getClientesChurnedEnSubPeriodo(Carbon $fechaInicioSubPeriodo, Carbon $fechaFinSubPeriodo, int $churnDefinitionMonths): int
    {
        return Cliente::where('is_churned', true)
            ->where(function ($query) use ($fechaInicioSubPeriodo, $fechaFinSubPeriodo, $churnDefinitionMonths) {
                // Su "punto de churn" (last_activity_at o created_at + $churnDefinitionMonths) cayó DENTRO del sub-periodo
                $query->whereRaw(
                    "DATE_ADD(IFNULL(last_activity_at, created_at), INTERVAL ? MONTH) >= ?",
                    [$churnDefinitionMonths, $fechaInicioSubPeriodo->toDateString()]
                )
                    ->whereRaw(
                        "DATE_ADD(IFNULL(last_activity_at, created_at), INTERVAL ? MONTH) <= ?",
                        [$churnDefinitionMonths, $fechaFinSubPeriodo->toDateString()]
                    );
            })
            ->count();
    }

    public function index(Request $request)
    {
        $resultados = null;
        $monthlyChurnData = [];
        $fechaInicioInput = $request->input('fecha_inicio', Carbon::now()->subMonths(3)->startOfMonth()->toDateString());
        $fechaFinInput = $request->input('fecha_fin', Carbon::now()->endOfMonth()->toDateString());
        $churnDefinitionMonths = 2;

        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $request->validate([
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            ]);

            $fechaInicioPeriodo = Carbon::parse($fechaInicioInput)->startOfDay();
            $fechaFinPeriodo = Carbon::parse($fechaFinInput)->endOfDay();

            // Cálculos para el periodo total
            $clientesAlInicioTotal = $this->getClientesActivosAlInicio($fechaInicioPeriodo, $churnDefinitionMonths);
            $clientesChurnedTotal = $this->getClientesChurnedEnSubPeriodo($fechaInicioPeriodo, $fechaFinPeriodo, $churnDefinitionMonths);

            $tasaChurnTotal = 0;
            if ($clientesAlInicioTotal > 0) {
                $tasaChurnTotal = ($clientesChurnedTotal / $clientesAlInicioTotal) * 100;
            }

            $resultados = [
                'periodo_analizado' => "De {$fechaInicioPeriodo->isoFormat('LL')} a {$fechaFinPeriodo->isoFormat('LL')}",
                'definicion_churn' => "{$churnDefinitionMonths} meses sin actividad",
                'clientes_al_inicio_periodo' => $clientesAlInicioTotal,
                'clientes_que_hicieron_churn_en_periodo' => $clientesChurnedTotal,
                'tasa_de_churn_calculada_total' => round($tasaChurnTotal, 2), // Porcentaje
                'clientes_activos_al_final_estimado' => $clientesAlInicioTotal - $clientesChurnedTotal,
            ];
            Log::info("[ChurnController] Resultados Totales: ", $resultados);

            // Cálculos para el gráfico de tendencia mensual
            $monthlyData = [];
            $period = CarbonPeriod::create($fechaInicioPeriodo->copy()->startOfMonth(), '1 month', $fechaFinPeriodo->copy()->endOfMonth());

            foreach ($period as $date) {
                $mesInicio = $date->copy()->startOfMonth();
                $mesFin = $date->copy()->endOfMonth();

                // Asegurarse de no ir más allá del periodo general seleccionado
                if ($mesInicio->gt($fechaFinPeriodo) || $mesFin->lt($fechaInicioPeriodo)) {
                    continue;
                }
                // Ajustar mesFin si es el último mes del periodo seleccionado
                if ($mesFin->gt($fechaFinPeriodo)) {
                    $mesFin = $fechaFinPeriodo->copy();
                }
                // Ajustar mesInicio si es el primer mes
                if ($mesInicio->lt($fechaInicioPeriodo) && $date->isSameMonth($fechaInicioPeriodo)) {
                    $mesInicio = $fechaInicioPeriodo->copy();
                }


                $clientesAlInicioMes = $this->getClientesActivosAlInicio($mesInicio, $churnDefinitionMonths);
                $clientesChurnedMes = $this->getClientesChurnedEnSubPeriodo($mesInicio, $mesFin, $churnDefinitionMonths);

                $tasaChurnMes = 0;
                if ($clientesAlInicioMes > 0) {
                    $tasaChurnMes = ($clientesChurnedMes / $clientesAlInicioMes) * 100;
                }

                $monthlyData[] = [
                    'mes' => $date->isoFormat('MMM YYYY'),
                    'tasa_churn' => round($tasaChurnMes, 2),
                    'clientes_churned' => $clientesChurnedMes,
                    'clientes_inicio_mes' => $clientesAlInicioMes,
                ];
            }
            $monthlyChurnData = $monthlyData;
            Log::info("[ChurnController] Datos Mensuales para Gráfico: ", $monthlyChurnData);
        }

        return view('admin.churn.index', [
            'resultados' => $resultados,
            'monthlyChurnData' => $monthlyChurnData,
            'fecha_inicio_actual' => $fechaInicioInput,
            'fecha_fin_actual' => $fechaFinInput,
        ]);
    }
}