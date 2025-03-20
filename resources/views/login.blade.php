<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Ruta para mostrar el formulario de login
Route::get('/login', function () {
    return view('login');
})->name('login');

// Ruta para procesar el login
Route::post('/login', function (Request $request) {
    $credentials = $request->only('username', 'password');

    // Credenciales estáticas de prueba
    $validUser = 'admin';
    $validPassword = '123456';

    if ($credentials['username'] === $validUser && $credentials['password'] === $validPassword) {
        return redirect('/dashboard')->with('success', 'Inicio de sesión exitoso');
    }
    
    return back()->withErrors(['login' => 'Usuario o contraseña incorrectos'])->withInput();
});

// Vista de login (resources/views/login.blade.php)
?>

<!-- login.blade.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    @if ($errors->any())
        <div style="color: red;">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label>Usuario:</label>
        <input type="text" name="username" required><br>
        <label>Contraseña:</label>
        <input type="password" name="password" required><br>
        <button type="submit">Iniciar sesión</button>
    </form>
</body>
</html>
