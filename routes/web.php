<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Empleado;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WhatsappController;

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::post('/login', function (Request $request) {
    $request->validate([
        'usuario' => 'required', 
        'password' => 'required'
    ]);
    $empleado = Empleado::where('usuario', $request->usuario)->first();
    if ($empleado && Hash::check($request->password, $empleado->contrasena)) {
        auth()->login($empleado);
        return redirect()->intended('/dashboard');
    }
    return back()->withErrors([
        'usuario' => 'Credenciales incorrectas.',
    ])->onlyInput('usuario');
})->name('login.post');

// Ruta que utiliza el DashboardController para cargar el view con los datos
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
