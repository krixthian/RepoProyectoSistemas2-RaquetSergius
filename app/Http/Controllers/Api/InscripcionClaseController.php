<?php

namespace App\Http\Controllers;

use App\Models\InscripcionClase;
use App\Models\ClaseZumba;
use App\Models\Cliente;
use App\Models\Reserva;
use Illuminate\Http\Request;

class InscripcionClaseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $inscripciones = InscripcionClase::with(['clase', 'cliente', 'reserva'])->get();
        return response()->json($inscripciones);
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
            'clase_id' => 'required|exists:clases_zumba,clase_id',
            'cliente_id' => 'required|exists:clientes,cliente_id',
            'reserva_id' => 'required|exists:reservas,reserva_id',
            'fecha_inscripcion' => 'required|date_format:Y-m-d H:i:s|before_or_equal:now',
            'asistio' => 'nullable|boolean',
        ]);

        $inscripcion = new InscripcionClase();
        $inscripcion->clase_id = $request->clase_id;
        $inscripcion->cliente_id = $request->cliente_id;
        $inscripcion->reserva_id = $request->reserva_id;
        $inscripcion->fecha_inscripcion = $request->fecha_inscripcion;
        $inscripcion->asistio = $request->asistio;
        $inscripcion->save();

        return response()->json(['message' => 'Inscripción a clase creada exitosamente', 'inscripcion' => $inscripcion->load(['clase', 'cliente', 'reserva'])], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\InscripcionClase  $inscripcionClase
     * @return \Illuminate\Http\Response
     */
    public function show(InscripcionClase $inscripcionClase)
    {
        return response()->json($inscripcionClase->load(['clase', 'cliente', 'reserva']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\InscripcionClase  $inscripcionClase
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, InscripcionClase $inscripcionClase)
    {
        $request->validate([
            'clase_id' => 'sometimes|exists:clases_zumba,clase_id',
            'cliente_id' => 'sometimes|exists:clientes,cliente_id',
            'reserva_id' => 'sometimes|exists:reservas,reserva_id',
            'fecha_inscripcion' => 'sometimes|date_format:Y-m-d H:i:s|before_or_equal:now',
            'asistio' => 'nullable|boolean',
        ]);

        if ($request->has('clase_id'))
            $inscripcionClase->clase_id = $request->clase_id;
        if ($request->has('cliente_id'))
            $inscripcionClase->cliente_id = $request->cliente_id;
        if ($request->has('reserva_id'))
            $inscripcionClase->reserva_id = $request->reserva_id;
        if ($request->has('fecha_inscripcion'))
            $inscripcionClase->fecha_inscripcion = $request->fecha_inscripcion;
        if ($request->has('asistio'))
            $inscripcionClase->asistio = $request->asistio;
        $inscripcionClase->save();

        return response()->json(['message' => 'Inscripción a clase actualizada exitosamente', 'inscripcion' => $inscripcionClase->load(['clase', 'cliente', 'reserva'])]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\InscripcionClase  $inscripcionClase
     * @return \Illuminate\Http\Response
     */
    public function destroy(InscripcionClase $inscripcionClase)
    {
        $inscripcionClase->delete();
        return response()->json(['message' => 'Inscripción a clase eliminada exitosamente']);
    }
}