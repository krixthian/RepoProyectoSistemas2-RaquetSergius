<?php

use Illuminate\Support\Facades\Route;

// Ruta para mostrar el formulario de login
Route::get('/login', function () {
    return view('login');
})->name('login');
