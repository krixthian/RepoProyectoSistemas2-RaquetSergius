<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cliente;
use App\Models\PuntosLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UpdateChurnStatusCommand extends Command
{
    protected $signature = 'app:update-churn-status';
    protected $description = 'Actualiza el estado de churn de los clientes basado en su última actividad general.';

    public function handle()
    {
        $this->info('Iniciando actualización de estado de churn...');
        Log::info('[UpdateChurnStatusCommand] Iniciando tarea.');

        $churnPeriodMonths = 2;
        $cutoffDate = Carbon::now()->subMonths($churnPeriodMonths);
        $puntosReactivacion = 50;
        $potentialChurnClientes = Cliente::where('is_churned', false)
            ->where(function ($query) use ($cutoffDate) {
                $query->where('last_activity_at', '<=', $cutoffDate)
                    ->orWhere(function ($subQuery) use ($cutoffDate) {
                        $subQuery->whereNull('last_activity_at')
                            ->where('created_at', '<=', $cutoffDate);
                    });
            })
            ->get();

        $churnedCount = 0;
        foreach ($potentialChurnClientes as $cliente) {
            DB::transaction(function () use ($cliente, $puntosReactivacion, &$churnedCount) {
                $puntosAntes = $cliente->puntos ?? 0; // Puntos antes de la reactivación

                $cliente->is_churned = true;
                $cliente->puntos = $puntosAntes + $puntosReactivacion;
                $cliente->save();

                $puntosDespues = $cliente->puntos; // Puntos después de la reactivación

                PuntosLog::create([
                    'cliente_id' => $cliente->cliente_id,
                    'accion' => 'Bono por Inactividad',
                    'puntos_cambio' => $puntosReactivacion,
                    'puntos_antes' => $puntosAntes,
                    'puntos_despues' => $puntosDespues,
                    'detalle' => "Otorgados {$puntosReactivacion} puntos por inactividad prolongada.",
                    'fecha' => Carbon::now(),

                ]);

                $churnedCount++;
                Log::info("[UpdateChurnStatusCommand] Cliente ID {$cliente->cliente_id} marcado como churn. Puntos otorgados: {$puntosReactivacion}. Puntos antes: {$puntosAntes}, Puntos después: {$puntosDespues}.");
            });
        }

        // Clientes que estaban en churn pero tuvieron actividad reciente
        $returnedClientes = Cliente::where('is_churned', true)
            ->where('last_activity_at', '>', $cutoffDate)
            ->get();

        $returnedCount = 0;
        foreach ($returnedClientes as $cliente) {
            $cliente->is_churned = false;
            $cliente->save();
            $returnedCount++;
            Log::info("[UpdateChurnStatusCommand] Cliente ID {$cliente->cliente_id} ya no está en churn (última actividad: {$cliente->last_activity_at}).");
        }

        $this->info("Actualización completada. Clientes marcados como churn en esta ejecución: {$churnedCount}. Clientes recuperados de churn en esta ejecución: {$returnedCount}.");
        Log::info("[UpdateChurnStatusCommand] Tarea finalizada. Marcados como churn: {$churnedCount}, Recuperados: {$returnedCount}.");
        return 0;
    }
}