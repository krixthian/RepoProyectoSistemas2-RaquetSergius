<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use App\Models\Equipo;
use App\Models\Torneo;
use App\Models\Cliente;
use Illuminate\Http\Request;

class EquipoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $equipos = Equipo::with(['torneo', 'capitan'])->get();
        return response()->json($equipos);
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
            'torneo_id' => 'required|exists:torneos,torneo_id',
            'capitan_id' => 'required|exists:clientes,cliente_id',
        ]);

        $equipo = new Equipo();
        $equipo->nombre = $request->nombre;
        $equipo->torneo_id = $request->torneo_id;
        $equipo->capitan_id = $request->capitan_id;
        $equipo->save();

        return response()->json(['message' => 'Equipo creado exitosamente', 'equipo' => $equipo->load(['torneo', 'capitan'])], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Equipo  $equipo
     * @return \Illuminate\Http\Response
     */
    public function show(Equipo $equipo)
    {
        return response()->json($equipo->load(['torneo', 'capitan']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Equipo  $equipo
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Equipo $equipo)
    {
        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'torneo_id' => 'sometimes|exists:torneos,torneo_id',
            'capitan_id' => 'sometimes|exists:clientes,cliente_id',
        ]);

        if ($request->has('nombre'))
            $equipo->nombre = $request->nombre;
        if ($request->has('torneo_id'))
            $equipo->torneo_id = $request->torneo_id;
        if ($request->has('capitan_id'))
            $equipo->capitan_id = $request->capitan_id;
        $equipo->save();

        return response()->json(['message' => 'Equipo actualizado exitosamente', 'equipo' => $equipo->load(['torneo', 'capitan'])]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Equipo  $equipo
     * @return \Illuminate\Http\Response
     */
    public function destroy(Equipo $equipo)
    {
        $equipo->delete();
        return response()->json(['message' => 'Equipo eliminado exitosamente']);
    }
}