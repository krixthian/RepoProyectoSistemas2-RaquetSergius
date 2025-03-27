<?php

namespace App\Http\Controllers;

use App\Models\Torneo;
use App\Models\Evento;
use Illuminate\Http\Request;

class TorneoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $torneos = Torneo::with('evento')->get();
        return response()->json($torneos);
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
            'evento_id' => 'required|exists:eventos,evento_id',
            'categoria' => 'required|string|max:255',
            'num_equipos' => 'required|integer|min:1',
            'estado' => 'required|in:programado,en curso,finalizado',
            'deporte' => 'required|in:wally,zumba',
        ]);

        $torneo = new Torneo();
        $torneo->evento_id = $request->evento_id;
        $torneo->categoria = $request->categoria;
        $torneo->num_equipos = $request->num_equipos;
        $torneo->estado = $request->estado;
        $torneo->deporte = $request->deporte;
        $torneo->save();

        return response()->json(['message' => 'Torneo creado exitosamente', 'torneo' => $torneo->load('evento')], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Torneo  $torneo
     * @return \Illuminate\Http\Response
     */
    public function show(Torneo $torneo)
    {
        return response()->json($torneo->load('evento'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Torneo  $torneo
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Torneo $torneo)
    {
        $request->validate([
            'evento_id' => 'sometimes|exists:eventos,evento_id',
            'categoria' => 'sometimes|string|max:255',
            'num_equipos' => 'sometimes|integer|min:1',
            'estado' => 'sometimes|in:programado,en curso,finalizado',
            'deporte' => 'sometimes|in:wally,zumba',
        ]);

        if ($request->has('evento_id'))
            $torneo->evento_id = $request->evento_id;
        if ($request->has('categoria'))
            $torneo->categoria = $request->categoria;
        if ($request->has('num_equipos'))
            $torneo->num_equipos = $request->num_equipos;
        if ($request->has('estado'))
            $torneo->estado = $request->estado;
        if ($request->has('deporte'))
            $torneo->deporte = $request->deporte;
        $torneo->save();

        return response()->json(['message' => 'Torneo actualizado exitosamente', 'torneo' => $torneo->load('evento')]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Torneo  $torneo
     * @return \Illuminate\Http\Response
     */
    public function destroy(Torneo $torneo)
    {
        $torneo->delete();
        return response()->json(['message' => 'Torneo eliminado exitosamente']);
    }
}