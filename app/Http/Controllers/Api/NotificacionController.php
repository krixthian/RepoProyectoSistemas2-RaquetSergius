<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use App\Models\Cliente;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $notificaciones = Notificacion::with('cliente')->get();
        return response()->json($notificaciones);
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
            'tipo' => 'required|string|in:recordatorio,promocion,confirmacion',
            'contenido' => 'required|string',
            'fecha_envio' => 'required|date_format:Y-m-d H:i:s',
            'enviada' => 'nullable|boolean',
        ]);

        $notificacion = new Notificacion();
        $notificacion->cliente_id = $request->cliente_id;
        $notificacion->tipo = $request->tipo;
        $notificacion->contenido = $request->contenido;
        $notificacion->fecha_envio = $request->fecha_envio;
        $notificacion->enviada = $request->enviada ?? false;
        $notificacion->save();

        return response()->json(['message' => 'Notificación creada exitosamente', 'notificacion' => $notificacion->load('cliente')], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Notificacion  $notificacion
     * @return \Illuminate\Http\Response
     */
    public function show(Notificacion $notificacion)
    {
        return response()->json($notificacion->load('cliente'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Notificacion  $notificacion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Notificacion $notificacion)
    {
        $request->validate([
            'cliente_id' => 'sometimes|exists:clientes,cliente_id',
            'tipo' => 'sometimes|string|in:recordatorio,promocion,confirmacion',
            'contenido' => 'sometimes|string',
            'fecha_envio' => 'sometimes|date_format:Y-m-d H:i:s',
            'enviada' => 'nullable|boolean',
        ]);

        if ($request->has('cliente_id'))
            $notificacion->cliente_id = $request->cliente_id;
        if ($request->has('tipo'))
            $notificacion->tipo = $request->tipo;
        if ($request->has('contenido'))
            $notificacion->contenido = $request->contenido;
        if ($request->has('fecha_envio'))
            $notificacion->fecha_envio = $request->fecha_envio;
        if ($request->has('enviada'))
            $notificacion->enviada = $request->enviada;
        $notificacion->save();

        return response()->json(['message' => 'Notificación actualizada exitosamente', 'notificacion' => $notificacion->load('cliente')]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Notificacion  $notificacion
     * @return \Illuminate\Http\Response
     */
    public function destroy(Notificacion $notificacion)
    {
        $notificacion->delete();
        return response()->json(['message' => 'Notificación eliminada exitosamente']);
    }
}