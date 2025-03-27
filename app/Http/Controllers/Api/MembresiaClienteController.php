<?php

namespace App\Http\Controllers;

use App\Models\MembresiaCliente;
use App\Models\Cliente;
use App\Models\PlanMembresia;
use Illuminate\Http\Request;

class MembresiaClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $membresiasCliente = MembresiaCliente::with(['cliente', 'plan'])->get();
        return response()->json($membresiasCliente);
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
            'cliente_id' => 'required|exists:clientes,cliente_id',
            'plan_id' => 'required|exists:planes_membresia,plan_id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'activa' => 'nullable|boolean',
        ]);

        $membresiaCliente = new MembresiaCliente();
        $membresiaCliente->cliente_id = $request->cliente_id;
        $membresiaCliente->plan_id = $request->plan_id;
        $membresiaCliente->fecha_inicio = $request->fecha_inicio;
        $membresiaCliente->fecha_fin = $request->fecha_fin;
        $membresiaCliente->activa = $request->activa ?? true;
        $membresiaCliente->save();

        return response()->json(['message' => 'Membresía de cliente creada exitosamente', 'membresia_cliente' => $membresiaCliente->load(['cliente', 'plan'])], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MembresiaCliente  $membresiaCliente
     * @return \Illuminate\Http\Response
     */
    public function show(MembresiaCliente $membresiaCliente)
    {
        return response()->json($membresiaCliente->load(['cliente', 'plan']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MembresiaCliente  $membresiaCliente
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MembresiaCliente $membresiaCliente)
    {
        $request->validate([
            'cliente_id' => 'sometimes|exists:clientes,cliente_id',
            'plan_id' => 'sometimes|exists:planes_membresia,plan_id',
            'fecha_inicio' => 'sometimes|date',
            'fecha_fin' => 'sometimes|date|after_or_equal:fecha_inicio',
            'activa' => 'nullable|boolean',
        ]);

        if ($request->has('cliente_id'))
            $membresiaCliente->cliente_id = $request->cliente_id;
        if ($request->has('plan_id'))
            $membresiaCliente->plan_id = $request->plan_id;
        if ($request->has('fecha_inicio'))
            $membresiaCliente->fecha_inicio = $request->fecha_inicio;
        if ($request->has('fecha_fin'))
            $membresiaCliente->fecha_fin = $request->fecha_fin;
        if ($request->has('activa'))
            $membresiaCliente->activa = $request->activa;
        $membresiaCliente->save();

        return response()->json(['message' => 'Membresía de cliente actualizada exitosamente', 'membresia_cliente' => $membresiaCliente->load(['cliente', 'plan'])]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MembresiaCliente  $membresiaCliente
     * @return \Illuminate\Http\Response
     */
    public function destroy(MembresiaCliente $membresiaCliente)
    {
        $membresiaCliente->delete();
        return response()->json(['message' => 'Membresía de cliente eliminada exitosamente']);
    }
}