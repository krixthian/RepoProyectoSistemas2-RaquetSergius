<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use Illuminate\Http\Request;

class InstructorController extends Controller
{
    /**
     * Muestra una lista de todos los instructores.
     */
    public function index()
    {
        $instructores = Instructor::latest()->get();
        return view('instructores.index', compact('instructores'));
    }

    /**
     * Muestra el formulario para crear un nuevo instructor.
     */
    public function create()
    {
        return view('instructores.create');
    }

    /**
     * Guarda un nuevo instructor en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'especialidad' => 'required|string|max:255',
            'tarifa_hora' => 'required|numeric|min:0',
        ]);

        Instructor::create($request->all());

        return redirect()->route('instructores.index')
            ->with('success', 'Instructor creado exitosamente.');
    }

    /**
     * Muestra el formulario para editar un instructor específico.
     */
    public function edit(Instructor $instructor)
    {
        return view('instructores.edit', compact('instructor'));
    }

    /**
     * Actualiza un instructor específico en la base de datos.
     */
    public function update(Request $request, Instructor $instructor)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'especialidad' => 'required|string|max:255',
            'tarifa_hora' => 'required|numeric|min:0',
        ]);

        $instructor->update($request->all());

        return redirect()->route('instructores.index')
            ->with('success', 'Instructor actualizado exitosamente.');
    }

    /**
     * Elimina un instructor de la base de datos.
     */
    public function destroy(Instructor $instructor)
    {
        try {
            $instructor->delete();
            return redirect()->route('instructores.index')
                ->with('success', 'Instructor eliminado exitosamente.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Maneja el error si el instructor no se puede borrar por tener clases asociadas
            return redirect()->route('instructores.index')
                ->with('error', 'No se puede eliminar al instructor porque está asociado a una o más clases.');
        }
    }
}