<?php

namespace App\Http\Controllers;

use App\Models\Cancha;
use Illuminate\Http\Request;

class CanchaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $canchas = Cancha::all();
        return response()->json($canchas);
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
            'tipo' => 'required|string|max:255',
            'disponible' => 'nullable|boolean',
            'precio_hora' => 'required|numeric|min:0',
            'capacidad' => 'required|integer|min:1',
        ]);

        $cancha = new Cancha();
        $cancha->nombre = $request->nombre;
        $cancha->tipo = $request->tipo;
        $cancha->disponible = $request->disponible ?? true;
        $cancha->precio_hora = $request->precio_hora;
        $cancha->capacidad = $request->capacidad;
        $cancha->save();

        return response()->json(['message' => 'Cancha creada exitosamente', 'cancha' => $cancha], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Cancha  $cancha
     * @return \Illuminate\Http\Response
     */
    public function show(Cancha $cancha)
    {
        return response()->json($cancha);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Cancha  $cancha
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Cancha $cancha)
    {
        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'tipo' => 'sometimes|string|max:255',
            'disponible' => 'nullable|boolean',
            'precio_hora' => 'sometimes|numeric|min:0',
            'capacidad' => 'sometimes|integer|min:1',
        ]);

        if ($request->has('nombre'))
            $cancha->nombre = $request->nombre;
        if ($request->has('tipo'))
            $cancha->tipo = $request->tipo;
        if ($request->has('disponible'))
            $cancha->disponible = $request->disponible;
        if ($request->has('precio_hora'))
            $cancha->precio_hora = $request->precio_hora;
        if ($request->has('capacidad'))
            $cancha->capacidad = $request->capacidad;
        $cancha->save();

        return response()->json(['message' => 'Cancha actualizada exitosamente', 'cancha' => $cancha]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Cancha  $cancha
     * @return \Illuminate\Http\Response
     */
    public function destroy(Cancha $cancha)
    {
        $cancha->delete();
        return response()->json(['message' => 'Cancha eliminada exitosamente']);
    }
}