<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Cliente;
use App\Models\Empleado;
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

        // 3. Estados de reservas
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

        // 5. Cantidad de empleados por rol
        $empleadosPorRol = Empleado::select('rol', DB::raw('COUNT(*) as total'))
            ->groupBy('rol')
            ->get();

        // 6. Clientes registrados por semana
        $clientesPorSemana = Cliente::select(
                DB::raw('YEAR(fecha_registro) as anio'),
                DB::raw('WEEK(fecha_registro, 1) as semana'), // Usar modo ISO para semanas
                DB::raw('CONCAT("Sem ", WEEK(fecha_registro, 1)) as semana_label'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('anio', 'semana', 'semana_label')
            ->orderBy('anio', 'desc')
            ->orderBy('semana', 'desc')
            ->limit(8)
            ->get();

        // 7. Estado de clientes (activos/inactivos)
        $clientesActivos = Cliente::select(
                DB::raw('CASE WHEN cliente_frecuente = 1 THEN "Activo" ELSE "Inactivo" END as estado'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('estado')
            ->get();

        // Fallbacks para datos faltantes
        // Top 5 clientes
        if ($topClientes->isEmpty()) {
            $topClientesLabels = ['Cliente A','Cliente B','Cliente C','Cliente D','Cliente E'];
            $topClientesData   = [12,9,7,5,3];
        } else {
            $topClientesLabels = $topClientes->pluck('nombre');
            $topClientesData   = $topClientes->pluck('total');
        }

        // Reservas por fecha
        if ($reservasPorFecha->isEmpty()) {
            $reservasFechaLabels = ['2025-01-01','2025-01-02','2025-01-03','2025-01-04','2025-01-05'];
            $reservasFechaData   = [5,8,6,10,4];
        } else {
            $reservasFechaLabels = $reservasPorFecha->pluck('fecha');
            $reservasFechaData   = $reservasPorFecha->pluck('total');
        }

        // Estados
        if ($estados->isEmpty()) {
            $estadosLabels = ['confirmada','cancelada','pendiente'];
            $estadosData   = [15,4,6];
        } else {
            $estadosLabels = $estados->pluck('estado');
            $estadosData   = $estados->pluck('total');
        }

        // Uso de canchas
        if ($usoCanchas->isEmpty()) {
            $usoCanchasLabels = ['Fútbol','Tenis','Padel','Basket','Vóley'];
            $usoCanchasData   = [20,15,10,8,5];
        } else {
            $usoCanchasLabels = $usoCanchas->pluck('nombre');
            $usoCanchasData   = $usoCanchas->pluck('total');
        }

        // Empleados por rol
        if ($empleadosPorRol->isEmpty()) {
            $empleadosLabels = ['Administrador', 'Recepcionista', 'Mantenimiento'];
            $empleadosData = [2, 5, 3];
        } else {
            $empleadosLabels = $empleadosPorRol->pluck('rol');
            $empleadosData = $empleadosPorRol->pluck('total');
        }

        // Clientes por semana
        if ($clientesPorSemana->isEmpty()) {
            $semanasLabels = ['Sem 45', 'Sem 46', 'Sem 47', 'Sem 48'];
            $semanasData = [15, 22, 18, 25];
        } else {
            $semanasLabels = $clientesPorSemana->pluck('semana_label');
            $semanasData = $clientesPorSemana->pluck('total');
        }

        // Estado de clientes
        if ($clientesActivos->isEmpty()) {
            $clientesEstadoLabels = ['Activo', 'Inactivo'];
            $clientesEstadoData = [85, 15];
        } else {
            $clientesEstadoLabels = $clientesActivos->pluck('estado');
            $clientesEstadoData = $clientesActivos->pluck('total');
        }

        return view('dashboard', compact(
            'topClientesLabels','topClientesData',
            'reservasFechaLabels','reservasFechaData',
            'estadosLabels','estadosData',
            'usoCanchasLabels','usoCanchasData',
            'empleadosLabels','empleadosData',
            'semanasLabels','semanasData',
            'clientesEstadoLabels','clientesEstadoData'
        ));
    }
}