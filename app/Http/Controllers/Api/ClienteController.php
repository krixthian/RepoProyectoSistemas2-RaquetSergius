<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $clientes = Cliente::all();
        return response()->json($clientes);
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
            'telefono' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'cliente_frecuente' => 'nullable|boolean',
            'fecha_registro' => 'required|date',
        ]);

        $cliente = new Cliente();
        $cliente->nombre = $request->nombre;
        $cliente->telefono = $request->telefono;
        $cliente->email = $request->email;
        $cliente->cliente_frecuente = $request->cliente_frecuente ?? false;
        $cliente->fecha_registro = $request->fecha_registro;
        $cliente->save();

        return response()->json(['message' => 'Cliente creado exitosamente', 'cliente' => $cliente], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Cliente  $cliente
     * @return \Illuminate\Http\Response
     */
    public function show(Cliente $cliente)
    {
        return response()->json($cliente);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Cliente  $cliente
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Cliente $cliente)
    {
        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'telefono' => 'sometimes|string|max:20',
            'email' => 'nullable|email|max:255',
            'cliente_frecuente' => 'nullable|boolean',
            'fecha_registro' => 'sometimes|date',
        ]);

        if ($request->has('nombre'))
            $cliente->nombre = $request->nombre;
        if ($request->has('telefono'))
            $cliente->telefono = $request->telefono;
        if ($request->has('email'))
            $cliente->email = $request->email;
        if ($request->has('cliente_frecuente'))
            $cliente->cliente_frecuente = $request->cliente_frecuente;
        if ($request->has('fecha_registro'))
            $cliente->fecha_registro = $request->fecha_registro;
        $cliente->save();

        return response()->json(['message' => 'Cliente actualizado exitosamente', 'cliente' => $cliente]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Cliente  $cliente
     * @return \Illuminate\Http\Response
     */
    public function destroy(Cliente $cliente)
    {
        $cliente->delete();
        return response()->json(['message' => 'Cliente eliminado exitosamente']);
    }
}