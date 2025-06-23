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

use App\Http\Controllers\PremioController;

use App\Http\Controllers\TorneoController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\IndexEmpleadoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\Admin\ChurnController;
use App\Http\Controllers\reservasControllers\ReservaControllerComp;
//...
use App\Http\Controllers\zumba\InscripcionZumbaCompController;
use App\Http\Controllers\zumba\ReservaZumbaController; 


Route::get('/login', function () {
    if (auth()->check()) {
        return redirect()->intended(route('admin.empleados.index'));
    }
    return view('login');
})->name('login');


// --- RUTAS PROTEGIDAS  ---
Route::middleware(['auth'])->group(function () {


    Route::get('/', [IndexEmpleadoController::class, 'index'])->name('admin.empleados.index');
    Route::get('/admin/empleados', [IndexEmpleadoController::class, 'index'])->name('admin.empleados.index');

    Route::get('/canchas/disponibilidad', [\App\Http\Controllers\DisponibilidadController::class, 'index'])->name('canchas.disponibilidad');

    Route::get('/reservas/opciones', [ReservaControllerComp::class, 'opciones'])->name('admin.reservas.opciones');
    Route::get('/reservas/opciones/pendientes', [ReservaControllerComp::class, 'index'])->name('admin.reservas.pendientes');
    Route::get('/reservas/opciones/pendientes/{id_reserva}', [ReservaControllerComp::class, 'verReserva'])->name('admin.reservas.ver');

    Route::post('/reservas/{id_reserva}/confirmar', [ReservaControllerComp::class, 'confirmarReserva'])->name('reservas.confirmar');
    Route::post('/reservas/{id_reserva}/rechazar', [ReservaControllerComp::class, 'rechazarReserva'])->name('reservas.rechazar');

    //RESERVAS
    Route::resource('reservas', ReservaController::class);

    // --- RUTAS ZUMBA ---
    Route::prefix('/zumba')->name('zumba.')->group(function () {
        Route::get('/opciones', [InscripcionZumbaCompController::class, 'opciones'])->name('opciones');
        Route::get('/pendientes', [InscripcionZumbaCompController::class, 'index'])->name('pendientes');
        Route::get('/ver-comprobante/{cliente_id}/{comprobante_hash}', [InscripcionZumbaCompController::class, 'verComprobante'])->name('verComprobante');
        Route::post('/confirmar/{cliente_id}/{comprobante_hash}', [InscripcionZumbaCompController::class, 'confirmarInscripciones'])->name('pendientes.confirmar');
        Route::post('/rechazar/{cliente_id}/{comprobante_hash}', [InscripcionZumbaCompController::class, 'rechazarInscripciones'])->name('pendientes.rechazar');

        // Horarios de clase
        Route::get('/agendar', [InscripcionZumbaCompController::class, 'showAgendarForm'])->name('agendar');
        Route::post('/agendar', [InscripcionZumbaCompController::class, 'storeAgendar'])->name('agendar.store');

        Route::get('/reservas', [ReservaZumbaController::class, 'index'])->name('reservas.index');

        // Formulario para crear nueva reserva Zumba
        Route::get('/reservas/create', [ReservaZumbaController::class, 'create'])->name('reservas.create');

        // Guardar reserva Zumba
        Route::post('/reservas', [ReservaZumbaController::class, 'store'])->name('reservas.store');
    });


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


        return redirect()->intended(route('admin.empleados.index'));
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
Route::resource('premios', PremioController::class);

// ------------------------------------


Route::resource('empleados', App\Http\Controllers\EmpleadoController::class);
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');