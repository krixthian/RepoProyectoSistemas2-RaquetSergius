<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Premio;
use App\Models\PuntosLog;
use App\Models\CanjePremio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PuntosController extends Controller
{
    /**
     * Muestra el menú de opciones para la gestión de clientes.
     */
    public function opciones()
    {
        return view('clientes.opciones');
    }

    /**
     * Muestra el formulario para sumar o restar puntos a un cliente.
     */
    public function showSumarRestarForm()
    {
        $clientes = Cliente::orderBy('nombre')->get();
        return view('puntos.sumar-restar', compact('clientes'));
    }

    /**
     * Procesa la adición o sustracción de puntos.
     */
    public function storePuntos(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,cliente_id',
            'accion' => 'required|in:sumar,restar',
            'puntos' => 'required|integer|min:1',
            'motivo' => 'required|string|max:255',
        ]);

        $cliente = Cliente::findOrFail($request->cliente_id);
        $puntosAntes = $cliente->puntos;

        DB::beginTransaction();
        try {
            if ($request->accion === 'sumar') {
                $cliente->puntos += $request->puntos;
            } else {
                if ($cliente->puntos < $request->puntos) {
                    return back()->withErrors(['puntos' => 'El cliente no tiene suficientes puntos para restar.'])->withInput();
                }
                $cliente->puntos -= $request->puntos;
            }
            $cliente->save();

            PuntosLog::create([
                'cliente_id' => $cliente->cliente_id,
                'accion' => $request->accion === 'sumar' ? 'Adicion Manual' : 'Sustraccion Manual',
                'puntos_cambio' => $request->accion === 'sumar' ? $request->puntos : -$request->puntos,
                'puntos_antes' => $puntosAntes,
                'puntos_despues' => $cliente->puntos,
                'detalle' => $request->motivo,
                'fecha' => now(),
            ]);

            DB::commit();

            return redirect()->route('clientes.opciones')->with('success', 'Puntos actualizados correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar puntos: ' . $e->getMessage());
            return back()->with('error', 'Ocurrió un error al actualizar los puntos.');
        }
    }

    /**
     * Muestra el formulario para canjear premios.
     */
    public function showCanjearForm()
    {
        $clientes = Cliente::orderBy('nombre')->get();
        $premios = Premio::where('activo', true)->where(function ($query) {
            $query->whereNull('stock')->orWhere('stock', '>', 0);
        })->get();

        return view('puntos.canjear', compact('clientes', 'premios'));
    }

    /**
     * Procesa el canje de un premio.
     */
    public function storeCanje(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,cliente_id',
            'premio_id' => 'required|exists:premios,premio_id',
        ]);

        $cliente = Cliente::findOrFail($request->cliente_id);
        $premio = Premio::findOrFail($request->premio_id);

        if ($cliente->puntos < $premio->puntos_requeridos) {
            return back()->withErrors(['premio_id' => 'El cliente no tiene suficientes puntos para este premio.'])->withInput();
        }

        if ($premio->stock !== null && $premio->stock <= 0) {
            return back()->withErrors(['premio_id' => 'Este premio está agotado.'])->withInput();
        }

        DB::beginTransaction();
        try {
            $puntosAntes = $cliente->puntos;
            $cliente->puntos -= $premio->puntos_requeridos;
            $cliente->save();

            if ($premio->stock !== null) {
                $premio->stock -= 1;
                $premio->save();
            }

            $canje = CanjePremio::create([
                'cliente_id' => $cliente->cliente_id,
                'premio_id' => $premio->premio_id,
                'puntos_utilizados' => $premio->puntos_requeridos,
                'fecha_canje' => now(),
                'estado' => 'Realizado',
            ]);

            PuntosLog::create([
                'cliente_id' => $cliente->cliente_id,
                'accion' => 'Canje de Premio',
                'puntos_cambio' => -$premio->puntos_requeridos,
                'puntos_antes' => $puntosAntes,
                'puntos_despues' => $cliente->puntos,
                'detalle' => "Canje del premio: {$premio->nombre}",
                'canje_premio_id' => $canje->canje_id,
                'fecha' => now(),
            ]);

            DB::commit();

            return redirect()->route('clientes.opciones')->with('success', "Premio '{$premio->nombre}' canjeado exitosamente por {$cliente->nombre}.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al canjear premio: ' . $e->getMessage());
            return back()->with('error', 'Ocurrió un error al procesar el canje.');
        }
    }

    public function historialCanjes()
    {
        // Usamos eager loading con 'cliente' y 'premio' para optimizar las consultas
        // y paginamos los resultados para un mejor rendimiento.
        $canjes = CanjePremio::with(['cliente', 'premio'])
            ->orderBy('fecha_canje', 'desc')
            ->paginate(15);

        return view('puntos.historial', compact('canjes'));
    }

    public function historialPuntosLog(Request $request)
    {
        $query = PuntosLog::with('cliente')->orderBy('fecha', 'desc');

        // Aplicar filtro por nombre de cliente
        if ($request->filled('nombre_cliente')) {
            $query->whereHas('cliente', function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->nombre_cliente . '%');
            });
        }

        // Aplicar filtro por teléfono de cliente
        if ($request->filled('telefono_cliente')) {
            $query->whereHas('cliente', function ($q) use ($request) {
                $q->where('telefono', 'like', '%' . $request->telefono_cliente . '%');
            });
        }

        // Paginamos los resultados y mantenemos los filtros en la paginación
        $logs = $query->paginate(20)->withQueryString();

        return view('puntos.historial-log', [
            'logs' => $logs,
            'filters' => $request->only(['nombre_cliente', 'telefono_cliente'])
        ]);
    }
}