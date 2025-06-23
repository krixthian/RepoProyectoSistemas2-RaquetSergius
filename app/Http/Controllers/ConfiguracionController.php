<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ConfiguracionController extends Controller
{
    /**
     * Muestra la página de configuración del sitio.
     */
    public function index()
    {
        return view('configuracion.index');
    }

    /**
     * Actualiza las imágenes del sitio.
     */
    public function updateImagenes(Request $request)
    {
        $request->validate([
            'horarios_zumba' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'qr_pago_club' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $successMessages = [];

        if ($request->hasFile('horarios_zumba')) {
            // Se mueve el archivo nuevo a la carpeta public, reemplazando el anterior.
            $request->file('horarios_zumba')->move(public_path('image'), 'horarios_zumba.jpg');
            $successMessages[] = 'La imagen de horarios de Zumba fue actualizada.';
        }

        if ($request->hasFile('qr_pago_club')) {
            // Se mueve el archivo nuevo a la carpeta public, reemplazando el anterior.
            $request->file('qr_pago_club')->move(public_path('image'), 'qr_pago_club.png');
            $successMessages[] = 'La imagen del QR de pago fue actualizada.';
        }

        if (empty($successMessages)) {
            return back()->with('info', 'No se seleccionó ninguna imagen para actualizar.');
        }

        return back()->with('success', implode(' ', $successMessages));
    }
}