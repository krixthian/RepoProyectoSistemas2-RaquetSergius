<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Empleado;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IndexEmpleadoController;
use Illuminate\Support\Facades\Log; // Añade esto al inicio del archivo si no está
use Illuminate\Support\Facades\Auth; // Añade esto al inicio del archivo si no está


use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ReservaController;


Route::get('/login', function () {

    if (auth()->check()) {
        return redirect()->intended('/admin/empleados');
    }
    return view('login');
})->name('login');

Route::post('/login', function (Request $request) {
    Log::info('Attempting login for user: ' . $request->usuario); // Log del intento
    $request->validate([
        'usuario' => 'required',
        'password' => 'required'
    ]);

    $empleado = Empleado::where('usuario', $request->usuario)->first();

    if ($empleado && Hash::check($request->password, $empleado->contrasena)) {
        Log::info('Password check successful for user: ' . $empleado->usuario);

        auth()->login($empleado, $request->filled('remember'));
        Log::info('Auth::login called. Auth check immediately after: ' . (Auth::check() ? 'Authenticated, User ID: ' . Auth::id() : 'Not Authenticated'));
        Log::info('Session ID after login call: ' . session()->getId());

        $request->session()->regenerate();
        Log::info('Session regenerated. New Session ID: ' . session()->getId());
        Log::info('Auth check after session regenerate: ' . (Auth::check() ? 'Authenticated, User ID: ' . Auth::id() : 'Not Authenticated'));

        return redirect()->intended('/admin/empleados');
    }

    Log::warning('Login failed for user: ' . $request->usuario); // Log de fallo
    return back()->withErrors([
        'usuario' => 'Credenciales incorrectas.',
    ])->onlyInput('usuario');
})->name('login.post');


Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');


// --- RUTAS PROTEGIDAS ---

Route::middleware(['auth'])->group(function () {
    Route::get('/', [IndexEmpleadoController::class, 'index'])->name('admin.empleados.index');
    Route::get('/admin/empleados', [IndexEmpleadoController::class, 'index'])->name('admin.empleados.index');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('reservas', ReservaController::class);

});


// Ruta para el logout (Es buena práctica tener una ruta POST para logout)
Route::post('/logout', function (Request $request) {
    auth()->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');
