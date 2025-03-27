<?php

namespace App\Http\Controllers;

use App\Models\PlanMembresia;
use Illuminate\Http\Request;

class PlanMembresiaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $planesMembresia = PlanMembresia::all();
        return response()->json($planesMembresia);
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
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'duracion_dias' => 'required|integer|min:1',
            'descuento_reservas' => 'required|numeric|min:0|max:100',
        ]);

        $planMembresia = new PlanMembresia();
        $planMembresia->nombre = $request->nombre;
        $planMembresia->descripcion = $request->descripcion;
        $planMembresia->precio = $request->precio;
        $planMembresia->duracion_dias = $request->duracion_dias;
        $planMembresia->descuento_reservas = $request->descuento_reservas;
        $planMembresia->save();

        return response()->json(['message' => 'Plan de membresía creado exitosamente', 'plan_membresia' => $planMembresia], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PlanMembresia  $planMembresia
     * @return \Illuminate\Http\Response
     */
    public function show(PlanMembresia $planMembresia)
    {
        return response()->json($planMembresia);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PlanMembresia  $planMembresia
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PlanMembresia $planMembresia)
    {
        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'sometimes|numeric|min:0',
            'duracion_dias' => 'sometimes|integer|min:1',
            'descuento_reservas' => 'sometimes|numeric|min:0|max:100',
        ]);

        if ($request->has('nombre'))
            $planMembresia->nombre = $request->nombre;
        if ($request->has('descripcion'))
            $planMembresia->descripcion = $request->descripcion;
        if ($request->has('precio'))
            $planMembresia->precio = $request->precio;
        if ($request->has('duracion_dias'))
            $planMembresia->duracion_dias = $request->duracion_dias;
        if ($request->has('descuento_reservas'))
            $planMembresia->descuento_reservas = $request->descuento_reservas;
        $planMembresia->save();

        return response()->json(['message' => 'Plan de membresía actualizado exitosamente', 'plan_membresia' => $planMembresia]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PlanMembresia  $planMembresia
     * @return \Illuminate\Http\Response
     */
    public function destroy(PlanMembresia $planMembresia)
    {
        $planMembresia->delete();
        return response()->json(['message' => 'Plan de membresía eliminado exitosamente']);
    }
}