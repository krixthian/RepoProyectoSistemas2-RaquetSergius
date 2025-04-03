<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Cancha;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservaController extends Controller
{
    /**
     * Muestra una lista de las reservas.
     */
    public function index()
    {
        $reservas = Reserva::with(['cliente', 'canchas'])
                           ->orderBy('fecha_hora_inicio', 'desc')
                           ->get();

        return view('reservas.index', compact('reservas'));
    }

    /**
     * Muestra el formulario para crear una nueva reserva.
     */
    public function create()
    {
        $clientes = Cliente::orderBy('nombre')->get();
        $canchas = Cancha::orderBy('nombre')->get();

        return view('reservas.create', compact('clientes', 'canchas'));
    }

    /**
     * Guarda una nueva reserva en la base de datos.
     */
    public function store(Request $request)
    {
        // La validación necesita asegurar que los IDs existan en las tablas correctas
        // y que los campos necesarios para ambas tablas (principal y pivote) estén presentes.
        $validatedData = $request->validate([
            // Campos para la tabla 'reservas' (Modelo Reserva)
            'cliente_id' => 'required|exists:clientes,cliente_id', // Valida contra tabla 'clientes'
            'fecha_hora_inicio' => 'required|date',
            'fecha_hora_fin' => 'required|date|after:fecha_hora_inicio',
            'monto' => 'required|numeric|min:0',
            'estado' => 'required|string|max:50',
            'metodo_pago' => 'nullable|string|max:50',
            'pago_completo' => 'required|boolean',

            // Campos para la tabla pivote 'reservas_canchas'
            'cancha_id' => 'required|exists:canchas,cancha_id',       // Valida contra tabla 'canchas'
            'precio_total_cancha' => 'required|numeric|min:0', // Precio específico para la cancha en esta reserva
        ]);

        DB::beginTransaction();

        try {
            // 1. Crear la Reserva principal en la tabla 'reservas'
            //    (El modelo Reserva ahora apunta a 'reservas')
            $reserva = Reserva::create([
                'cliente_id' => $validatedData['cliente_id'],
                'fecha_hora_inicio' => $validatedData['fecha_hora_inicio'],
                'fecha_hora_fin' => $validatedData['fecha_hora_fin'],
                'monto' => $validatedData['monto'], // Este es el monto TOTAL de la reserva
                'estado' => $validatedData['estado'],
                'metodo_pago' => $validatedData['metodo_pago'],
                'pago_completo' => $validatedData['pago_completo'],
            ]);

            // 2. Asociar la Cancha en la tabla pivote 'reservas_canchas' usando la relación
            //    Obtenemos el ID de la reserva recién creada ($reserva->reserva_id)
            //    y el ID de la cancha validada ($validatedData['cancha_id']).
            //    Pasamos los datos extra para la tabla pivote como segundo argumento de attach().
            if ($reserva) { // Asegurarse que la reserva se creó
                $reserva->canchas()->attach($validatedData['cancha_id'], [
                    'precio_total' => $validatedData['precio_total_cancha']
                    // 'created_at' y 'updated_at' se manejan automáticamente si usas withTimestamps()
                ]);
            } else {
                // Si la reserva no se pudo crear por alguna razón (aunque create lanzaría excepción)
                throw new \Exception("No se pudo crear el registro principal de la reserva.");
            }


            DB::commit();

            return redirect()->route('reservas.index')
                             ->with('success', 'Reserva creada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al crear reserva: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString()); // Loguear más detalle

            // Podrías querer dar un mensaje más específico si es posible
            return back()->withInput()
                         ->withErrors(['error_general' => 'Ocurrió un error al guardar la reserva. Revisa los logs para más detalles. Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Muestra los detalles de una reserva específica.
     */
    public function show(Reserva $reserva)
    {
        $reserva->load(['cliente', 'canchas']);
        return view('reservas.show', compact('reserva'));
    }
}
