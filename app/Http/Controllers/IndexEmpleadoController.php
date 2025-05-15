<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // Importar Log
use Illuminate\Support\Facades\Auth; // Importar Auth

class IndexEmpleadoController extends Controller
{
    public function index()
    {
        Log::info('Accessing /admin/empleados route.');
        Log::info('Auth check in IndexEmpleadoController: ' . (Auth::check() ? 'Authenticated' : 'Not Authenticated'));
        if (Auth::check()) {
            Log::info('Authenticated User ID in Controller: ' . Auth::id());
            Log::info('Authenticated User Name in Controller: ' . Auth::user()->nombre); // O ->usuario
        } else {
            Log::info('User is not authenticated when accessing controller.');
        }
        Log::info('Session ID in Controller: ' . session()->getId());
        Log::info('Session data in Controller: ', session()->all()); // Muestra todos los datos de la sesión

        $empleados = Empleado::orderBy('nombre')->paginate(15);
        return view('admin.empleados.index', compact('empleados'));
    }
}