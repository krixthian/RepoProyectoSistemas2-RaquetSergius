<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\Http\Request;

class EventoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $eventos = Evento::all();
        return response()->json($eventos);
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
            'fecha_inicio' => 'required|date_format:Y-m-d H:i:s|after_or_equal:now',
            'fecha_fin' => 'required|date_format:Y-m-d H:i:s|after:fecha_inicio',
            'tipo' => 'required|string|in:torneo,exhibicion,clase especial',
            'precio_inscripcion' => 'nullable|numeric|min:0',
        ]);

        $evento = new Evento();
        $evento->nombre = $request->nombre;
        $evento->descripcion = $request->descripcion;
        $evento->fecha_inicio = $request->fecha_inicio;
        $evento->fecha_fin = $request->fecha_fin;
        $evento->tipo = $request->tipo;
        $evento->precio_inscripcion = $request->precio_inscripcion ?? 0;
        $evento->save();

        return response()->json(['message' => 'Evento creado exitosamente', 'evento' => $evento], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Evento  $evento
     * @return \Illuminate\Http\Response
     */
    public function show(Evento $evento)
    {
        return response()->json($evento);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Evento  $evento
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Evento $evento)
    {
        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'sometimes|date_format:Y-m-d H:i:s|after_or_equal:now',
            'fecha_fin' => 'sometimes|date_format:Y-m-d H:i:s|after:fecha_inicio',
            'tipo' => 'sometimes|string|in:torneo,exhibicion,clase especial',
            'precio_inscripcion' => 'nullable|numeric|min:0',
        ]);

        if ($request->has('nombre'))
            $evento->nombre = $request->nombre;
        if ($request->has('descripcion'))
            $evento->descripcion = $request->descripcion;
        if ($request->has('fecha_inicio'))
            $evento->fecha_inicio = $request->fecha_inicio;
        if ($request->has('fecha_fin'))
            $evento->fecha_fin = $request->fecha_fin;
        if ($request->has('tipo'))
            $evento->tipo = $request->tipo;
        if ($request->has('precio_inscripcion'))
            $evento->precio_inscripcion = $request->precio_inscripcion;
        $evento->save();

        return response()->json(['message' => 'Evento actualizado exitosamente', 'evento' => $evento]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Evento  $evento
     * @return \Illuminate\Http\Response
     */
    public function destroy(Evento $evento)
    {
        $evento->delete();
        return response()->json(['message' => 'Evento eliminado exitosamente']);
    }
}