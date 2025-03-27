<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use App\Models\Reserva;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReservaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $reservas = Reserva::with('cliente')->get();
        return response()->json($reservas);
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
            'fecha_hora_inicio' => 'required|date_format:Y-m-d H:i:s|after_or_equal:now',
            'fecha_hora_fin' => 'required|date_format:Y-m-d H:i:s|after:fecha_hora_inicio',
            'monto' => 'required|numeric|min:0',
            'estado' => 'required|in:pendiente,confirmada,cancelada',
            'metodo_pago' => 'required|in:QR,efectivo',
            'pago_completo' => 'nullable|boolean',
        ]);

        $reserva = new Reserva();
        $reserva->cliente_id = $request->cliente_id;
        $reserva->fecha_hora_inicio = $request->fecha_hora_inicio;
        $reserva->fecha_hora_fin = $request->fecha_hora_fin;
        $reserva->monto = $request->monto;
        $reserva->estado = $request->estado;
        $reserva->metodo_pago = $request->metodo_pago;
        $reserva->pago_completo = $request->pago_completo ?? false;
        $reserva->save();

        return response()->json(['message' => 'Reserva creada exitosamente', 'reserva' => $reserva->load('cliente')], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Reserva  $reserva
     * @return \Illuminate\Http\Response
     */
    public function show(Reserva $reserva)
    {
        return response()->json($reserva->load('cliente'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Reserva  $reserva
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Reserva $reserva)
    {
        $request->validate([
            'cliente_id' => 'sometimes|exists:clientes,cliente_id',
            'fecha_hora_inicio' => 'sometimes|date_format:Y-m-d H:i:s|after_or_equal:now',
            'fecha_hora_fin' => 'sometimes|date_format:Y-m-d H:i:s|after:fecha_hora_inicio',
            'monto' => 'sometimes|numeric|min:0',
            'estado' => 'sometimes|in:pendiente,confirmada,cancelada',
            'metodo_pago' => 'sometimes|in:QR,efectivo',
            'pago_completo' => 'nullable|boolean',
        ]);

        if ($request->has('cliente_id'))
            $reserva->cliente_id = $request->cliente_id;
        if ($request->has('fecha_hora_inicio'))
            $reserva->fecha_hora_inicio = $request->fecha_hora_inicio;
        if ($request->has('fecha_hora_fin'))
            $reserva->fecha_hora_fin = $request->fecha_hora_fin;
        if ($request->has('monto'))
            $reserva->monto = $request->monto;
        if ($request->has('estado'))
            $reserva->estado = $request->estado;
        if ($request->has('metodo_pago'))
            $reserva->metodo_pago = $request->metodo_pago;
        if ($request->has('pago_completo'))
            $reserva->pago_completo = $request->pago_completo;
        $reserva->save();

        return response()->json(['message' => 'Reserva actualizada exitosamente', 'reserva' => $reserva->load('cliente')]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Reserva  $reserva
     * @return \Illuminate\Http\Response
     */
    public function destroy(Reserva $reserva)
    {
        $reserva->delete();
        return response()->json(['message' => 'Reserva eliminada exitosamente']);
    }
}