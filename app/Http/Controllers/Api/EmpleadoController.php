<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmpleadoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $empleados = Empleado::all();
        return response()->json($empleados);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // No es necesario un formulario en una API RESTful,
        // pero podrías retornar un JSON con la estructura esperada para la creación.
        return response()->json(['message' => 'Endpoint para crear un nuevo empleado']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'usuario' => 'required|string|unique:empleados|max:255',
            'contrasena' => 'required|string|min:8',
            'rol' => 'required|string|in:administrador,recepcionista,mantenimiento',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'activo' => 'nullable|boolean',
        ]);

        $empleado = new Empleado();
        $empleado->nombre = $request->nombre;
        $empleado->usuario = $request->usuario;
        $empleado->contrasena = Hash::make($request->contrasena);
        $empleado->rol = $request->rol;
        $empleado->telefono = $request->telefono;
        $empleado->email = $request->email;
        $empleado->activo = $request->activo ?? true;
        $empleado->save();

        return response()->json(['message' => 'Empleado creado exitosamente', 'empleado' => $empleado], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Empleado  $empleado
     * @return \Illuminate\Http\Response
     */
    public function show(Empleado $empleado)
    {
        return response()->json($empleado);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Empleado  $empleado
     * @return \Illuminate\Http\Response
     */
    public function edit(Empleado $empleado)
    {
        // No es necesario un formulario en una API RESTful.
        return response()->json(['message' => 'Endpoint para editar el empleado con ID: ' . $empleado->empleado_id]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Empleado  $empleado
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Empleado $empleado)
    {
        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'usuario' => ['sometimes', 'string', 'max:255', Rule::unique('empleados')->ignore($empleado->empleado_id, 'empleado_id')],
            'contrasena' => 'sometimes|string|min:8',
            'rol' => 'sometimes|string|in:administrador,recepcionista,mantenimiento',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'activo' => 'nullable|boolean',
        ]);

        if ($request->has('nombre'))
            $empleado->nombre = $request->nombre;
        if ($request->has('usuario'))
            $empleado->usuario = $request->usuario;
        if ($request->has('contrasena'))
            $empleado->contrasena = Hash::make($request->contrasena);
        if ($request->has('rol'))
            $empleado->rol = $request->rol;
        if ($request->has('telefono'))
            $empleado->telefono = $request->telefono;
        if ($request->has('email'))
            $empleado->email = $request->email;
        if ($request->has('activo'))
            $empleado->activo = $request->activo;
        $empleado->save();

        return response()->json(['message' => 'Empleado actualizado exitosamente', 'empleado' => $empleado]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Empleado  $empleado
     * @return \Illuminate\Http\Response
     */
    public function destroy(Empleado $empleado)
    {
        $empleado->delete();
        return response()->json(['message' => 'Empleado eliminado exitosamente']);
    }
}