<?php

namespace App\Http\Controllers;

use App\Models\Torneo;
use Illuminate\Http\Request;
use App\Models\Equipo;
use App\Models\Partido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TorneoController extends Controller
{
    /**
     * Display a listing of the resource.
     *

     */
    public function index()
    {
        $torneos = Torneo::all();
        return view('torneos.index', compact('torneos'));
    }

    /**
     * Show the form for creating a new resource.
     *

     */
    public function create()
    {
        $eventos = \App\Models\Evento::all(); // Asegúrate de que la ruta al modelo Evento sea correcta
        return view('torneos.create', compact('eventos'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request

     */
    public function store(Request $request)
    {
        $request->validate([
            'evento_id' => 'required|integer|exists:eventos,evento_id',
            'categoria' => 'required|string|max:255',
            'estado' => 'required|in:Pendiente,En Curso,Finalizado,Cancelado',
            'num_equipos' => 'required|integer|min:2', // Reintroducimos la validación
        ]);

        $torneo = Torneo::create($request->all());

        return redirect()->route('torneos.index')->with('success', 'Torneo creado exitosamente.');
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Torneo  $torneo

     */
    public function show(Torneo $torneo)
    {
        // Cargar equipos inscritos y partidos generados
        $torneo->load('equipos', 'partidos.equipoLocal', 'partidos.equipoVisitante');

        // Obtener todos los equipos que NO están en este torneo para el dropdown
        $equiposInscritosIds = $torneo->equipos->pluck('equipo_id')->all();
        $equiposDisponibles = Equipo::whereNotIn('equipo_id', $equiposInscritosIds)->get();

        return view('torneos.show', compact('torneo', 'equiposDisponibles'));
    }

    /**
     * Añade un equipo a un torneo.
     */
    public function addEquipo(Request $request, Torneo $torneo)
    {
        $request->validate(['equipo_id' => 'required|exists:equipos,equipo_id']);

        // Verificar si el torneo ya está lleno
        if ($torneo->equipos()->count() >= $torneo->num_equipos) {
            return back()->with('error', 'El torneo ya ha alcanzado el número máximo de equipos.');
        }

        // Añadir el equipo
        $torneo->equipos()->attach($request->equipo_id);

        return back()->with('success', 'Equipo añadido al torneo exitosamente.');
    }

    /**
     * Genera los partidos para un torneo en formato todos contra todos.
     */


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Torneo  $torneo

     */
    public function edit(Torneo $torneo)
    {
        return view('torneos.edit', compact('torneo')); // Ya no necesitamos pasar equipos
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Torneo  $torneo

     */
    public function update(Request $request, Torneo $torneo)
    {
        $request->validate([
            'categoria' => 'required|string|max:255',
            'estado' => 'required|in:Pendiente,En Curso,Finalizado,Cancelado',
            // 'num_equipos' => 'required|integer|min:2', // Eliminamos esta validación
            // 'equipos' => 'nullable|array|exists:equipos,equipo_id', // Eliminamos esta validación
            // 'deporte' => 'nullable|string|max:255', // Eliminamos esta validación
            'evento_id' => 'nullable|integer|exists:eventos,evento_id', // Mantenemos esta si la usas
        ]);

        $torneo->update($request->all());

        // No necesitamos sincronizar equipos aquí

        return redirect()->route('torneos.index')->with('success', 'Torneo actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Torneo  $torneo

     */
    public function destroy(Torneo $torneo)
    {
        // No necesitamos desvincular equipos aquí
        $torneo->delete();
        return redirect()->route('torneos.index')->with('success', 'Torneo eliminado exitosamente.');
    }
}