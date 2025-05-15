<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Torneo;
use App\Models\Cliente;
use Illuminate\Http\Request;

class EquipoController extends Controller
{
    public function index()
    {
        // Cargar las relaciones 'torneoPrincipal' y 'capitan' para optimizar consultas
        $equipos = Equipo::with(['torneoPrincipal', 'capitan'])->orderBy('nombre')->get();
        return view('equipos.index', compact('equipos'));
    }

    public function create()
    {
        $capitanes = Cliente::all();
        $torneos = Torneo::orderBy('deporte')->orderBy('categoria')->get();
        return view('equipos.create', compact('capitanes', 'torneos'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255',
            'capitan_id' => 'nullable|integer|exists:clientes,cliente_id',
            // El nombre del campo en el formulario es 'torneo_principal_id'
            'torneo_principal_id' => 'required|integer|exists:torneos,torneo_id',
        ]);

        $equipo = Equipo::create([
            'nombre' => $validatedData['nombre'],
            'capitan_id' => $validatedData['capitan_id'] ?? null,
            // Guardamos en la columna 'torneo_id' de la tabla 'equipos'
            'torneo_id' => $validatedData['torneo_principal_id'],
        ]);

        // Si manejas inscripciones adicionales a través de la tabla pivote
        if ($request->filled('torneo_id_para_asociar_pivote')) {
            $request->validate(['torneo_id_para_asociar_pivote' => 'integer|exists:torneos,torneo_id']);
            // Asegúrate de no duplicar la asociación si el torneo principal es el mismo
            if ($equipo->torneo_id != $request->torneo_id_para_asociar_pivote) {
                $equipo->torneos()->attach($request->torneo_id_para_asociar_pivote);
            }
        }

        return redirect()->route('equipos.index')->with('success', 'Equipo creado exitosamente.');
    }

    public function show(Equipo $equipo)
    {
        // Cargar las relaciones si no se cargaron automáticamente con el Route Model Binding
        $equipo->loadMissing(['torneoPrincipal', 'capitan', 'torneos']);
        return view('equipos.show', compact('equipo'));
    }

    public function edit(Equipo $equipo)
    {
        $capitanes = Cliente::all();
        // Necesitamos pasar la lista de todos los torneos para el select
        $torneos = Torneo::orderBy('deporte')->orderBy('categoria')->get();
        return view('equipos.edit', compact('equipo', 'capitanes', 'torneos'));
    }

    public function update(Request $request, Equipo $equipo)
    {
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255',
            'capitan_id' => 'nullable|integer|exists:clientes,cliente_id',
            // Validamos el torneo_principal_id que viene del formulario de edición
            'torneo_principal_id' => 'required|integer|exists:torneos,torneo_id',
        ]);

        $equipo->update([
            'nombre' => $validatedData['nombre'],
            'capitan_id' => $validatedData['capitan_id'] ?? null,
            // Actualizamos la columna 'torneo_id' con el valor seleccionado
            'torneo_id' => $validatedData['torneo_principal_id'],
        ]);

        // Opcional: Manejo de la tabla pivote 'torneo_equipo' al actualizar
        // if ($request->filled('torneo_id_para_asociar_pivote')) {
        //     $request->validate(['torneo_id_para_asociar_pivote' => 'integer|exists:torneos,torneo_id']);
        //     // sync() es útil aquí para actualizar las asociaciones de la tabla pivote.
        //     // Si el torneo principal también puede estar en la pivote, necesitarás lógica adicional.
        //     $idsParaPivot = [];
        //     if ($equipo->torneo_id != $request->torneo_id_para_asociar_pivote) {
        //        $idsParaPivot[] = $request->torneo_id_para_asociar_pivote;
        //     }
        //     $equipo->torneos()->sync($idsParaPivot);
        // } else {
        //     // Si no se envía nada para la pivote, quizás quieras desasociar todos.
        //     $equipo->torneos()->detach();
        // }

        return redirect()->route('equipos.index')->with('success', 'Equipo actualizado exitosamente.');
    }

    public function destroy(Equipo $equipo)
    {
        // Antes de eliminar el equipo, es buena práctica desasociarlo de tablas pivote.
        $equipo->torneos()->detach(); // Desasocia de la tabla torneo_equipo
        $equipo->delete();
        return redirect()->route('equipos.index')->with('success', 'Equipo eliminado exitosamente.');
    }
}