<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Empleado; // Asumiendo que este modelo existe y es autenticable
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WhatsappController; // Asumiendo que existe
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\TorneoController; // Importación correcta
use App\Http\Controllers\EquipoController; // Asegúrate de importar el controlador de Equipo

// --- RUTAS PÚBLICAS ---

// Ruta para mostrar el formulario de login
Route::get('/login', function () {
    // Si ya está logueado, redirigir al dashboard
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('login');
})->name('login');

// Ruta para procesar el login (Usando Empleado)
Route::post('/login', function (Request $request) {
    $request->validate([
        'usuario' => 'required',
        'password' => 'required'
    ]);

    $empleado = Empleado::where('usuario', $request->usuario)->first();

    if ($empleado && Hash::check($request->password, $empleado->contrasena)) {
        auth()->login($empleado, $request->filled('remember'));
        $request->session()->regenerate();
        return redirect()->intended('/dashboard');
    }

    return back()->withErrors([
        'usuario' => 'Credenciales incorrectas.',
    ])->onlyInput('usuario');
})->name('login.post');

// Rutas para la recuperación de contraseña
Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

// --- ¡RUTAS PÚBLICAS PARA TORNEOS! ---
// Al mover Route::resource aquí, ya no requieren login.
// GET /torneos (torneos.index) -> Muestra lista de torneos
// GET /torneos/create (torneos.create) -> Muestra formulario para crear torneo
// POST /torneos (torneos.store) -> Guarda el nuevo torneo
// GET /torneos/{torneo} (torneos.show) -> Muestra un torneo específico
// GET /torneos/{torneo}/edit (torneos.edit) -> Muestra formulario para editar torneo
// PUT/PATCH /torneos/{torneo} (torneos.update) -> Actualiza el torneo
// DELETE /torneos/{torneo} (torneos.destroy) -> Elimina el torneo
Route::resource('torneos', TorneoController::class);
// ------------------------------------

// --- ¡NUEVAS RUTAS PÚBLICAS PARA EQUIPOS! ---
// Estas rutas estarán accesibles sin necesidad de login.
Route::resource('equipos', EquipoController::class);
// -------------------------------------

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::resource('reservas', ReservaController::class);


// --- RUTAS PROTEGIDAS (REQUIEREN LOGIN) ---

// Agrupa todas las rutas que necesitan autenticación
Route::middleware(['auth'])->group(function () { // Asegúrate de que el guard 'web' esté configurado para Empleado si es necesario


    // Ruta para el logout (Es buena práctica tener una ruta POST para logout)
    Route::post('/logout', function (Request $request) {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');

    // Rutas para el CRUD de Reservas (usando ReservaController)

    // Aquí puedes añadir más rutas que requieran login
    // Ejemplo: Route::get('/mi-perfil', [ProfileController::class, 'show'])->name('profile.show');

}); // FIN DEL GRUPO DE MIDDLEWARE AUTH

// Ruta raíz
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Ruta de Whatsapp (si necesitas que sea pública o protegida, ajústala)
// Route::post('/whatsapp/send', [WhatsappController::class, 'sendMessage']);