<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Empleado;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ReservaController;

use App\Http\Controllers\TorneoController;
use App\Http\Controllers\EquipoController;

use App\Http\Controllers\IndexEmpleadoController;

use App\Http\Controllers\ClienteController;

use App\Http\Controllers\Admin\ChurnController;

Route::get('/login', function () {
    if (auth()->check()) {
        return redirect()->intended(route('admin.empleados.index'));
    }
    return view('login');
})->name('login');

Route::post('/login', function (Request $request) {
    Log::info('Attempting login for user: ' . $request->usuario);
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


        return redirect()->intended(route('admin.panel'));
    }

    Log::warning('Login failed for user: ' . $request->usuario);
    return back()->withErrors([
        'usuario' => 'Credenciales incorrectas.',
    ])->onlyInput('usuario');
})->name('login.post');

// Rutas para la recuperación de contraseña
Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

// --- RUTAS PÚBLICAS ---

Route::resource('torneos', TorneoController::class);
Route::resource('equipos', EquipoController::class);

Route::get('/admin/churn-analisis', [ChurnController::class, 'index'])->name('admin.churn.index');

Route::resource('clientes', ClienteController::class);
// ------------------------------------

// --- RUTAS PROTEGIDAS  ---
Route::middleware(['auth'])->group(function () {

    Route::get('/', [IndexEmpleadoController::class, 'index'])->name('admin.empleados.index');
    Route::get('/admin/empleados', [IndexEmpleadoController::class, 'index'])->name('admin.empleados.index');
    Route::resource('reservas', ReservaController::class);


    Route::get('/admin/panel', [IndexEmpleadoController::class, 'index'])->name('admin.empleados.index');


    Route::resource('reservas', ReservaController::class);

    Route::post('/logout', function (Request $request) {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');

});
// ------------------------------------


Route::resource('empleados', App\Http\Controllers\EmpleadoController::class);
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
