<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log; // Asegúrate de tener este import

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        Log::info('Mostrando formulario de solicitud de restablecimiento de contraseña.'); // Log al mostrar el form
        return view('auth.passwords.email');
    }

    public function sendResetLinkEmail(Request $request)
    {
        Log::info('----------------------------------------------------');
        Log::info('Inicio del método sendResetLinkEmail.');
        Log::info('Email recibido en la solicitud: ' . $request->input('email'));

        $request->validate(['email' => 'required|email']);
        Log::info('Validación del email completada.');

        // Determina el broker a usar. Debería ser 'empleados' según tu config.
        $brokerName = config('auth.defaults.passwords');
        Log::info('Usando el broker de contraseñas: ' . $brokerName);

        // Intenta enviar el enlace de restablecimiento
        $status = Password::broker($brokerName)->sendResetLink(
            $request->only('email')
        );

        Log::info('Resultado de Password::broker()->sendResetLink(): ' . $status); // Esto es crucial

        if ($status === Password::RESET_LINK_SENT) {
            Log::info('Enlace de restablecimiento ENVIADO (o al menos Laravel cree que lo hizo).');
            return back()->with('status', __($status));
        }

        Log::error('FALLO al enviar el enlace de restablecimiento. Estado: ' . $status);
        return back()->withErrors(['email' => __($status)]);
    }
}