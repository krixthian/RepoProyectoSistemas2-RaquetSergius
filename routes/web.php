<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Empleado; // Asumiendo que este modelo existe y es autenticable
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\WhatsappController; // Asumiendo que existe

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ReservaController; // <-- Importar el controlador de Reservas

// --- RUTAS PÚBLICAS ---

// Ruta para mostrar el formulario de login
Route::get('/login', function () {
    // Si ya está logueado, redirigir al dashboard
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('login');
})->name('login'); // Nombre 'login' es importante para el middleware 'auth'

// Ruta para procesar el login (Usando Empleado)
Route::post('/login', function (Request $request) {
    $request->validate([
        'usuario' => 'required',
        'password' => 'required'
    ]);

    // Asegúrate que tu modelo Empleado usa la columna 'contrasena'
    // y que implementa la interfaz Authenticatable o extiende la clase User base.
    $empleado = Empleado::where('usuario', $request->usuario)->first();

    if ($empleado && Hash::check($request->password, $empleado->contrasena)) {
        // Loguear al empleado
        auth()->login($empleado, $request->filled('remember')); // Añadir 'remember me' si tienes el checkbox
        // Regenerar sesión para seguridad
        $request->session()->regenerate();
        return redirect()->intended('/dashboard'); // Redirige a donde intentaba ir o al dashboard
    }

    return back()->withErrors([
        'usuario' => 'Credenciales incorrectas.',
    ])->onlyInput('usuario');
})->name('login.post'); // Puedes usar un nombre diferente si quieres

// Rutas para la recuperación de contraseña
// NOTA: Por defecto, estas rutas usan el provider 'users' de config/auth.php.
// Si quieres que funcionen con 'Empleado', DEBES configurar un provider
// para 'Empleado' en config/auth.php y posiblemente ajustar estos controladores.
Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');


// --- RUTAS PROTEGIDAS (REQUIEREN LOGIN) ---

// Agrupa todas las rutas que necesitan autenticación
//Route::middleware(['auth'])->group(function () {

    

//});
// Ruta que utiliza el DashboardController
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Ruta para el logout (Es buena práctica tener una ruta POST para logout)
Route::post('/logout', function (Request $request) {
    auth()->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// Rutas para el CRUD de Reservas (usando ReservaController)
// El middleware 'auth' ya protege todo este grupo
Route::resource('reservas', ReservaController::class);

// Aquí puedes añadir más rutas que requieran login
// Ejemplo: Route::get('/mi-perfil', [ProfileController::class, 'show'])->name('profile.show');

// Ruta raíz (opcional, puedes dirigirla a donde prefieras)
// Si quieres que la raíz redirija al login si no está autenticado,
// o al dashboard si sí lo está.
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Ruta de Whatsapp (si necesitas que sea pública o protegida, ajústala)
// Route::post('/whatsapp/send', [WhatsappController::class, 'sendMessage']);

//Ruta para clientes (dcmq)
use App\Http\Controllers\ClienteController;

Route::resource('clientes', ClienteController::class);
