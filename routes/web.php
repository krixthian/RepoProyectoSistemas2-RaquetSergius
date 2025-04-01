<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WhatsappController;

Route::get('/', function () {
    return view('welcome');
});

// Ruta que utiliza el DashboardController para cargar el view con los datos
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/webhook/', [WhatsappController::class, 'token']);
Route::post('/webhook/', [WhatsappController::class, 'escuchar']);
