<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use App\Models\Partido;
use App\Models\Torneo;
use App\Models\Equipo;
use App\Models\Cancha;
use Illuminate\Http\Request;

class PartidoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $partidos = Partido::with(['torneo', 'equipo1', 'equipo2', 'cancha'])->get();
        return response()->json($partidos);
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
            'torneo_id' => 'required|exists:torneos,torneo_id',
            'equipo1_id' => 'required|exists:equipos,equipo_id',
            'equipo2_id' => 'required|exists:equipos,equipo_id|different:equipo1_id',
            'cancha_id' => 'required|exists:canchas,cancha_id',
            'fecha_hora' => 'required|date_format:Y-m-d H:i:s|after_or_equal:now',
            'resultado' => 'nullable|string|max:255',
            'estado' => 'required|in:programado,finalizado,cancelado',
        ]);

        $partido = new Partido();
        $partido->torneo_id = $request->torneo_id;
        $partido->equipo1_id = $request->equipo1_id;
        $partido->equipo2_id = $request->equipo2_id;
        $partido->cancha_id = $request->cancha_id;
        $partido->fecha_hora = $request->fecha_hora;
        $partido->resultado = $request->resultado;
        $partido->estado = $request->estado;
        $partido->save();

        return response()->json(['message' => 'Partido creado exitosamente', 'partido' => $partido->load(['torneo', 'equipo1', 'equipo2', 'cancha'])], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Partido  $partido
     * @return \Illuminate\Http\Response
     */
    public function show(Partido $partido)
    {
        return response()->json($partido->load(['torneo', 'equipo1', 'equipo2', 'cancha']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Partido  $partido
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Partido $partido)
    {
        $request->validate([
            'torneo_id' => 'sometimes|exists:torneos,torneo_id',
            'equipo1_id' => 'sometimes|exists:equipos,equipo_id',
            'equipo2_id' => 'sometimes|exists:equipos,equipo_id|different:equipo1_id',
            'cancha_id' => 'sometimes|exists:canchas,cancha_id',
            'fecha_hora' => 'sometimes|date_format:Y-m-d H:i:s|after_or_equal:now',
            'resultado' => 'nullable|string|max:255',
            'estado' => 'sometimes|in:programado,finalizado,cancelado',
        ]);

        if ($request->has('torneo_id'))
            $partido->torneo_id = $request->torneo_id;
        if ($request->has('equipo1_id'))
            $partido->equipo1_id = $request->equipo1_id;
        if ($request->has('equipo2_id'))
            $partido->equipo2_id = $request->equipo2_id;
        if ($request->has('cancha_id'))
            $partido->cancha_id = $request->cancha_id;
        if ($request->has('fecha_hora'))
            $partido->fecha_hora = $request->fecha_hora;
        if ($request->has('resultado'))
            $partido->resultado = $request->resultado;
        if ($request->has('estado'))
            $partido->estado = $request->estado;
        $partido->save();

        return response()->json(['message' => 'Partido actualizado exitosamente', 'partido' => $partido->load(['torneo', 'equipo1', 'equipo2', 'cancha'])]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Partido  $partido
     * @return \Illuminate\Http\Response
     */
    public function destroy(Partido $partido)
    {
        $partido->delete();
        return response()->json(['message' => 'Partido eliminado exitosamente']);
    }
}