<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Empleado;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;

// Ruta para mostrar el formulario de login
Route::get('/login', function () {
    return view('login');
})->name('login');

// Ruta para procesar el login
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

// Rutas para la recuperación de contraseña

// Muestra el formulario para solicitar el enlace de restablecimiento
Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])
    ->name('password.request');

// Envía el enlace de restablecimiento al correo ingresado
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->name('password.email');

// Muestra el formulario para restablecer la contraseña, usando el token
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])
    ->name('password.reset');

// Procesa el restablecimiento de la contraseña
Route::post('password/reset', [ResetPasswordController::class, 'reset'])
    ->name('password.update');
