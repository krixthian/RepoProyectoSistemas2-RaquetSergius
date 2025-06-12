<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InscripcionClase;

class ReservaZumbaController extends Controller
{
    /**
     * Muestra una lista de todas las reservas de clases de Zumba.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Consulta actualizada con los nombres de relaciones y campos correctos.
        $reservas = InscripcionClase::with([
            'claseZumba.instructor', // CAMBIO: La relación ahora es 'claseZumba'
            'claseZumba.area',       // CAMBIO: La relación ahora es 'claseZumba'
            'cliente'
        ])
        ->orderBy('fecha_inscripcion', 'desc') // CAMBIO: Ordenamos por fecha_inscripcion
        ->get();

        return view('reservas.index', compact('reservas'));
    }
}