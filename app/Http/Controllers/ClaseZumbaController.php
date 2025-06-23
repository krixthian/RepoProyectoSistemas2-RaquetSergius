<?php

namespace App\Http\Controllers;

use App\Models\ClaseZumba;
use App\Models\Instructor;
use App\Models\AreaZumba;
use Illuminate\Http\Request;

class ClaseZumbaController extends Controller
{
    public function index()
    {
        $clases = ClaseZumba::with(['instructor', 'area'])->latest()->get();
        return view('clases_zumba.index', compact('clases'));
    }

    public function create()
    {
        $instructores = Instructor::where('habilitado', true)->get();
        $areas = AreaZumba::all();
        return view('clases_zumba.create', compact('instructores', 'areas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'instructor_id' => 'required|exists:instructores,instructor_id',
            'area_id' => 'required|exists:areas_zumba,area_id',
            'dia_semana' => 'required|string',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'cupo_maximo' => 'required|integer|min:1',
            'costo' => 'required|numeric|min:0',
            'estado' => 'required|string',
        ]);

        $data = $request->all();
        $data['cupos_disponibles'] = $request->cupo_maximo; // Al crear, los cupos disponibles son el máximo

        ClaseZumba::create($data);
        return redirect()->route('clases-zumba.index')->with('success', 'Clase de Zumba creada exitosamente.');
    }

    public function edit(ClaseZumba $clases_zumba)
    {
        $instructores = Instructor::get();
        $areas = AreaZumba::all();
        // El nombre de la variable debe coincidir con el del route-model binding de la ruta resource
        return view('clases_zumba.edit', ['clase' => $clases_zumba, 'instructores' => $instructores, 'areas' => $areas]);
    }

    public function update(Request $request, ClaseZumba $clases_zumba)
    {
        $request->validate([
            'instructor_id' => 'required|exists:instructores,instructor_id',
            'area_id' => 'required|exists:areas_zumba,area_id',
            'dia_semana' => 'required|string',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'cupo_maximo' => 'required|integer|min:1',
            'costo' => 'required|numeric|min:0',
            'estado' => 'required|string',
        ]);

        $clases_zumba->update($request->all());
        return redirect()->route('clases-zumba.index')->with('success', 'Clase de Zumba actualizada exitosamente.');
    }

    public function destroy(ClaseZumba $clases_zumba)
    {
        if ($clases_zumba->inscripciones()->exists()) {
            return back()->with('error', 'No se puede eliminar la clase porque tiene inscripciones activas.');
        }
        $clases_zumba->delete();
        return redirect()->route('clases-zumba.index')->with('success', 'Clase de Zumba eliminada exitosamente.');
    }
}