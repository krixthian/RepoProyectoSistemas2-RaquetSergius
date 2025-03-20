<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        // Credenciales estáticas
        $validCredentials = [
            'username' => 'admin',
            'password' => '123456',
        ];

        if ($credentials['username'] === $validCredentials['username'] && 
            $credentials['password'] === $validCredentials['password']) {
            // Autenticación manual
            Auth::loginUsingId(1); // Puedes usar un ID ficticio
            return redirect()->intended('/dashboard')->with('success', 'Inicio de sesión exitoso');
        }

        return back()->withErrors(['login' => 'Usuario o contraseña incorrectos'])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        return redirect('/login');
    }
}