<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::post('/login', function (Request $request) {
    $credentials = $request->only('username', 'password');

    
    $validCredentials = [
        'username' => 'admin',
        'password' => '123456',
    ];

    if ($credentials['username'] === $validCredentials['username'] && 
        $credentials['password'] === $validCredentials['password']) {
        return redirect('/dashboard')->with('success', 'Inicio de sesión exitoso');
    }
    
    return back()->withErrors(['login' => 'Usuario o contraseña incorrectos'])->withInput();
})->name('login');

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style></style>
</head>
<body>
    <div class="login-container">
        <h2>Raquet-Sergius</h2>
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <label for="username">Usuario:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Iniciar sesión</button>
        </form>
    </div>
</body>
</html>