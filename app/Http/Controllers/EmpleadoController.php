<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmpleadoController extends Controller
{
    public function index(Request $request)
    {
        $query = Empleado::query();

        if ($request->has('telefono') && $request->telefono != '') {
            $query->where('telefono', 'like', '%' . $request->telefono . '%');
        }

        $empleados = $query->get();

        return view('empleados.index', compact('empleados'));
    }

    public function create()
    {
        return view('empleados.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'usuario' => 'required|string|max:255|unique:empleados',
            'contrasena' => 'required|string|min:6',
            'rol' => 'required|string|max:50',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'activo' => 'boolean',
        ]);

        Empleado::create([
            'nombre' => $request->nombre,
            'usuario' => $request->usuario,
            'contrasena' => Hash::make($request->contrasena),
            'rol' => $request->rol,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'activo' => $request->activo ?? false,
        ]);

        return redirect()->route('empleados.index')->with('success', 'Empleado creado exitosamente.');
    }

    public function edit(Empleado $empleado)
    {
        return view('empleados.edit', compact('empleado'));
    }

    public function update(Request $request, Empleado $empleado)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'usuario' => 'required|string|max:255|unique:empleados,usuario,' . $empleado->empleado_id . ',empleado_id',
            'rol' => 'required|string|max:50',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'activo' => 'boolean',
        ]);

        $empleado->update([
            'nombre' => $request->nombre,
            'usuario' => $request->usuario,
            'rol' => $request->rol,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'activo' => $request->activo ?? false,
        ]);

        if ($request->filled('contrasena')) {
            $empleado->update(['contrasena' => Hash::make($request->contrasena)]);
        }

        return redirect()->route('empleados.index')->with('success', 'Empleado actualizado exitosamente.');
    }

    public function destroy(Empleado $empleado)
    {
        $empleado->delete();
        return redirect()->route('empleados.index')->with('success', 'Empleado eliminado.');
    }
}
