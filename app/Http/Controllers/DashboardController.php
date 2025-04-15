<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
{
    // 1. Membresías más utilizadas
    $membresias = DB::table('membresias_cliente as mc')
        ->join('planes_membresia as pm', 'mc.plan_id', '=', 'pm.plan_id')
        ->select('pm.nombre as plan', DB::raw('COUNT(mc.membresia_id) as total'))
        ->groupBy('pm.nombre')
        ->orderBy('total', 'desc')
        ->get();

    // Extraer las etiquetas y los datos
    $membresias_labels = $membresias->pluck('plan'); // Obtiene los nombres de los planes
    $membresias_data = $membresias->pluck('total'); // Obtiene el total de membresías

    // 2. Reservas por mes
    $reservas = DB::table('reservas')
        ->select(DB::raw("DATE_FORMAT(fecha, '%Y-%m') as mes"), DB::raw('COUNT(*) as total'))
        ->groupBy('mes')
        ->orderBy('mes')
        ->get();

    // Extraer las etiquetas y los datos
    $reservas_labels = $reservas->pluck('mes'); // Obtiene los meses
    $reservas_data = $reservas->pluck('total'); // Obtiene el total de reservas

    // 3. Estados de reservas
    $estados = DB::table('reservas')
        ->select('estado', DB::raw('COUNT(*) as total'))
        ->whereIn('estado', ['confirmada', 'cancelada'])
        ->groupBy('estado')
        ->get();

    // Extraer las etiquetas y los datos
    $estados_labels = $estados->pluck('estado'); // Obtiene los estados
    $estados_data = $estados->pluck('total'); // Obtiene el total de estados

    // 4. Ocupación clases Zumba
    $clasesZumba = DB::table('clases_zumba as cz')
        ->join('areas_zumba as az', 'cz.area_id', '=', 'az.area_id')
        ->select(
            DB::raw('CONCAT(DATE_FORMAT(cz.fecha_hora_inicio, "%Y-%m"), " ", az.nombre) as clase'),
            DB::raw('ROUND((cz.cupo_actual / cz.cupo_maximo) * 100, 2) as ocupacion')
        )
        ->orderBy('cz.fecha_hora_inicio')
        ->limit(10)
        ->get();

    // Extraer las etiquetas y los datos
    $clasesZumba_labels = $clasesZumba->pluck('clase'); // Obtiene los nombres de las clases
    $clasesZumba_data = $clasesZumba->pluck('ocupacion'); // Obtiene los porcentajes de ocupación

    // 5. Uso de canchas
    $usoCanchas = DB::table('reservas_canchas as rc')
        ->join('canchas as c', 'rc.cancha_id', '=', 'c.cancha_id')
        ->select('c.nombre', DB::raw('COUNT(rc.reserva_cancha_id) as total'))
        ->groupBy('c.nombre')
        ->orderBy('total', 'desc')
        ->get();

    // Extraer las etiquetas y los datos
    $usoCanchas_labels = $usoCanchas->pluck('nombre'); // Obtiene los nombres de las canchas
    $usoCanchas_data = $usoCanchas->pluck('total'); // Obtiene el total de reservas por cancha

    // 6. Torneos por estado
    $torneos = DB::table('torneos')
        ->select('estado', DB::raw('COUNT(*) as total'))
        ->groupBy('estado')
        ->get();

    // Extraer las etiquetas y los datos
    $torneos_labels = $torneos->pluck('estado'); // Obtiene los estados de los torneos
    $torneos_data = $torneos->pluck('total'); // Obtiene el total de torneos por estado

    // 7. Notificaciones
    $notificaciones = DB::table('notificaciones')
        ->select('tipo', DB::raw('COUNT(*) as total'))
        ->groupBy('tipo')
        ->get();

    // Extraer las etiquetas y los datos
    $notificaciones_labels = $notificaciones->pluck('tipo'); // Obtiene los tipos de notificaciones
    $notificaciones_data = $notificaciones->pluck('total'); // Obtiene el total de notificaciones por tipo

    // 8. Instructores más activos
    $instructores = DB::table('clases_zumba as cz')
        ->join('instructores as i', 'cz.instructor_id', '=', 'i.instructor_id')
        ->select('i.nombre', DB::raw('COUNT(cz.clase_id) as total_clases'))
        ->groupBy('i.nombre')
        ->orderBy('total_clases', 'desc')
        ->limit(5)
        ->get();

    // Extraer las etiquetas y los datos
    $instructores_labels = $instructores->pluck('nombre'); // Obtiene los nombres de los instructores
    $instructores_data = $instructores->pluck('total_clases'); // Obtiene el total de clases por instructor

    // 9. Tipos de eventos
    $eventos = DB::table('eventos')
        ->select('tipo', DB::raw('COUNT(*) as total'))
        ->groupBy('tipo')
        ->get();

    // Extraer las etiquetas y los datos
    $eventos_labels = $eventos->pluck('tipo'); // Obtiene los tipos de eventos
    $eventos_data = $eventos->pluck('total'); // Obtiene el total de eventos por tipo

    // Preparar todos los datos para la vista
    return view('dashboard', compact(
        'membresias_labels', 'membresias_data',
        'reservas_labels', 'reservas_data',
        'estados_labels', 'estados_data',
        'clasesZumba_labels', 'clasesZumba_data',
        'usoCanchas_labels', 'usoCanchas_data',
        'torneos_labels', 'torneos_data',
        'notificaciones_labels', 'notificaciones_data',
        'instructores_labels', 'instructores_data',
        'eventos_labels', 'eventos_data'
    ));
}
}
?>