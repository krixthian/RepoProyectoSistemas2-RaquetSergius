<?php

namespace App\Http\Controllers;

use App\Models\Premio;
use Illuminate\Http\Request;

class PremioController extends Controller
{
    public function index(Request $request)
    {
        $query = Premio::query();

        if ($request->has('search') && $request->search != '') {
            $query->where('nombre', 'like', '%' . $request->search . '%');
        }

        $premios = $query->get();

        return view('premios.index', compact('premios'));
    }

    public function create()
    {
        return view('premios.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'puntos_requeridos' => 'required|integer',
            'tipo' => 'required|string|max:255',
            'activo' => 'required|boolean',
        ]);

        Premio::create($request->all());

        return redirect()->route('premios.index')->with('success', 'Premio creado con éxito');
    }

    public function edit(Premio $premio)
    {
        return view('premios.edit', compact('premio'));
    }

    public function update(Request $request, Premio $premio)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'puntos_requeridos' => 'required|integer',
            'tipo' => 'required|string|max:255',
            'activo' => 'required|boolean',
        ]);

        $premio->update($request->all());

        return redirect()->route('premios.index')->with('success', 'Premio actualizado con éxito');
    }

    public function destroy(Premio $premio)
    {
        $premio->delete();

        return redirect()->route('premios.index')->with('success', 'Premio eliminado con éxito');
    }
}
