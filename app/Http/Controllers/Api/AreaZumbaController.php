<?php

namespace App\Http\Controllers;

use App\Models\AreaZumba;
use Illuminate\Http\Request;

class AreaZumbaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $areasZumba = AreaZumba::all();
        return response()->json($areasZumba);
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
            'capacidad' => 'required|integer|min:1',
            'disponible' => 'nullable|boolean',
            'precio_clase' => 'required|numeric|min:0',
        ]);

        $areaZumba = new AreaZumba();
        $areaZumba->nombre = $request->nombre;
        $areaZumba->capacidad = $request->capacidad;
        $areaZumba->disponible = $request->disponible ?? true;
        $areaZumba->precio_clase = $request->precio_clase;
        $areaZumba->save();

        return response()->json(['message' => 'Área Zumba creada exitosamente', 'area_zumba' => $areaZumba], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AreaZumba  $areaZumba
     * @return \Illuminate\Http\Response
     */
    public function show(AreaZumba $areaZumba)
    {
        return response()->json($areaZumba);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AreaZumba  $areaZumba
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AreaZumba $areaZumba)
    {
        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'capacidad' => 'sometimes|integer|min:1',
            'disponible' => 'nullable|boolean',
            'precio_clase' => 'sometimes|numeric|min:0',
        ]);

        if ($request->has('nombre'))
            $areaZumba->nombre = $request->nombre;
        if ($request->has('capacidad'))
            $areaZumba->capacidad = $request->capacidad;
        if ($request->has('disponible'))
            $areaZumba->disponible = $request->disponible;
        if ($request->has('precio_clase'))
            $areaZumba->precio_clase = $request->precio_clase;
        $areaZumba->save();

        return response()->json(['message' => 'Área Zumba actualizada exitosamente', 'area_zumba' => $areaZumba]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AreaZumba  $areaZumba
     * @return \Illuminate\Http\Response
     */
    public function destroy(AreaZumba $areaZumba)
    {
        $areaZumba->delete();
        return response()->json(['message' => 'Área Zumba eliminada exitosamente']);
    }
}