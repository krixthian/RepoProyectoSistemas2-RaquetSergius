<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use App\Models\Instructor;
use Illuminate\Http\Request;

class InstructorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $instructores = Instructor::all();
        return response()->json($instructores);
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
            'telefono' => 'required|string|max:20',
            'especialidad' => 'required|string|max:255',
            'tarifa_hora' => 'required|numeric|min:0',
        ]);

        $instructor = new Instructor();
        $instructor->nombre = $request->nombre;
        $instructor->telefono = $request->telefono;
        $instructor->especialidad = $request->especialidad;
        $instructor->tarifa_hora = $request->tarifa_hora;
        $instructor->save();

        return response()->json(['message' => 'Instructor creado exitosamente', 'instructor' => $instructor], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Instructor  $instructor
     * @return \Illuminate\Http\Response
     */
    public function show(Instructor $instructor)
    {
        return response()->json($instructor);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Instructor  $instructor
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Instructor $instructor)
    {
        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'telefono' => 'sometimes|string|max:20',
            'especialidad' => 'sometimes|string|max:255',
            'tarifa_hora' => 'sometimes|numeric|min:0',
        ]);

        if ($request->has('nombre'))
            $instructor->nombre = $request->nombre;
        if ($request->has('telefono'))
            $instructor->telefono = $request->telefono;
        if ($request->has('especialidad'))
            $instructor->especialidad = $request->especialidad;
        if ($request->has('tarifa_hora'))
            $instructor->tarifa_hora = $request->tarifa_hora;
        $instructor->save();

        return response()->json(['message' => 'Instructor actualizado exitosamente', 'instructor' => $instructor]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Instructor  $instructor
     * @return \Illuminate\Http\Response
     */
    public function destroy(Instructor $instructor)
    {
        $instructor->delete();
        return response()->json(['message' => 'Instructor eliminado exitosamente']);
    }
}