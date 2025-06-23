<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ZumbaOpcionesController extends Controller
{
    /**
     * Muestra el menú de opciones para la gestión de Zumba.
     */
    public function index()
    {
        return view('zumba.opciones');
    }
}