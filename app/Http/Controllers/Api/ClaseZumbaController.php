<?php

namespace App\Http\Controllers;

use App\Models\ClaseZumba;
use App\Models\AreaZumba;
use App\Models\Instructor;
use Illuminate\Http\Request;

class ClaseZumbaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $clasesZumba = ClaseZumba::with(['area', 'instructor'])->get();
        return response()->json($clasesZumba);
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
            'area_id' => 'required|exists:areas_zumba,area_id',
            'instructor_id' => 'required|exists:instructores,instructor_id',
            'fecha_hora_inicio' => 'required|date_format:Y-m-d H:i:s|after_or_equal:now',
            'fecha_hora_fin' => 'required|date_format:Y-m-d H:i:s|after:fecha_hora_inicio',
            'cupo_maximo' => 'required|integer|min:1',
            'precio' => 'required|numeric|min:0',
        ]);

        $claseZumba = new ClaseZumba();
        $claseZumba->area_id = $request->area_id;
        $claseZumba->instructor_id = $request->instructor_id;
        $claseZumba->fecha_hora_inicio = $request->fecha_hora_inicio;
        $claseZumba->fecha_hora_fin = $request->fecha_hora_fin;
        $claseZumba->cupo_maximo = $request->cupo_maximo;
        $claseZumba->precio = $request->precio;
        $claseZumba->save();

        return response()->json(['message' => 'Clase Zumba creada exitosamente', 'clase_zumba' => $claseZumba->load(['area', 'instructor'])], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ClaseZumba  $claseZumba
     * @return \Illuminate\Http\Response
     */
    public function show(ClaseZumba $claseZumba)
    {
        return response()->json($claseZumba->load(['area', 'instructor']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ClaseZumba  $claseZumba
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ClaseZumba $claseZumba)
    {
        $request->validate([
            'area_id' => 'sometimes|exists:areas_zumba,area_id',
            'instructor_id' => 'sometimes|exists:instructores,instructor_id',
            'fecha_hora_inicio' => 'sometimes|date_format:Y-m-d H:i:s|after_or_equal:now',
            'fecha_hora_fin' => 'sometimes|date_format:Y-m-d H:i:s|after:fecha_hora_inicio',
            'cupo_maximo' => 'sometimes|integer|min:1',
            'precio' => 'sometimes|numeric|min:0',
        ]);

        if ($request->has('area_id'))
            $claseZumba->area_id = $request->area_id;
        if ($request->has('instructor_id'))
            $claseZumba->instructor_id = $request->instructor_id;
        if ($request->has('fecha_hora_inicio'))
            $claseZumba->fecha_hora_inicio = $request->fecha_hora_inicio;
        if ($request->has('fecha_hora_fin'))
            $claseZumba->fecha_hora_fin = $request->fecha_hora_fin;
        if ($request->has('cupo_maximo'))
            $claseZumba->cupo_maximo = $request->cupo_maximo;
        if ($request->has('precio'))
            $claseZumba->precio = $request->precio;
        $claseZumba->save();

        return response()->json(['message' => 'Clase Zumba actualizada exitosamente', 'clase_zumba' => $claseZumba->load(['area', 'instructor'])]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ClaseZumba  $claseZumba
     * @return \Illuminate\Http\Response
     */
    public function destroy(ClaseZumba $claseZumba)
    {
        $claseZumba->delete();
        return response()->json(['message' => 'Clase Zumba eliminada exitosamente']);
    }
}