<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\whatsappController;

Route::get('/webhook/', [whatsappController::class, "token"]);

Route::post('/webhook/', [whatsappController::class, "escuchar"]);