<?php

// El namespace ahora coincide con la estructura de carpetas app/Http/Controllers/Zumba
namespace App\Http\Controllers\Zumba;

use App\Http\Controllers\Controller; // Es buena práctica importar el Controller base
use Illuminate\Http\Request;
use App\Models\InscripcionClase;
use App\Models\Cliente;
use App\Models\ClaseZumba;

class ReservaZumbaController extends Controller
{
    /**
     * Muestra una lista de todas las reservas de clases de Zumba.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = InscripcionClase::with('cliente', 'claseZumba.instructor', 'claseZumba.area')
            ->orderBy('fecha_clase', 'desc');

        // Filtro por nombre de cliente
        if ($request->filled('cliente_nombre')) {
            $query->whereHas('cliente', function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->cliente_nombre . '%');
            });
        }

        // Filtro por rango de fechas de la clase
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_clase', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_clase', '<=', $request->fecha_fin);
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $inscripciones = $query->paginate(15)->appends($request->query());

        return view('zumba.reservas.index', compact('inscripciones'));
    }

    /**
     * Muestra el formulario para crear una nueva reserva.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $clientes = Cliente::orderBy('nombre')->get();
        $clases = ClaseZumba::where('habilitado', true)
            ->with('instructor', 'area')
            ->get();

        // La vista debe estar en 'zumba.reservas.create'
        return view('zumba.reservas.create', compact('clientes', 'clases'));
    }

    /**
     * Guarda una nueva reserva en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    /**
     * Guarda una nueva reserva en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Paso 2: Validación actualizada
        $validatedData = $request->validate([
            'cliente_id' => 'required|exists:clientes,cliente_id',
            'clase_id' => 'required|exists:clases_zumba,clase_id',
            'fecha_clase' => 'required|date', // Se asegura que se envíe una fecha válida
            'monto_pagado' => 'required|numeric|min:0',
            'metodo_pago' => 'required|string|max:50',
        ]);

        // Paso 3: Creación con el nuevo campo
        InscripcionClase::create([
            'cliente_id' => $validatedData['cliente_id'],
            'clase_id' => $validatedData['clase_id'],
            'fecha_clase' => $validatedData['fecha_clase'], // Se guarda la fecha de la clase
            'monto_pagado' => $validatedData['monto_pagado'],
            'metodo_pago' => $validatedData['metodo_pago'],
            'fecha_inscripcion' => now(), // La fecha en que se hace el registro
            'estado' => 'Confirmada',
        ]);

        return redirect()->route('zumba.reservas.index')
            ->with('success', '¡Reserva creada exitosamente!');
    }
}