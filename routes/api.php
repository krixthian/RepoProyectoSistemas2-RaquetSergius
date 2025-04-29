<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CanchaController;
use App\Http\Controllers\Api\AreaZumbaController;
use App\Http\Controllers\Api\InstructorController;
use App\Http\Controllers\Api\ReservaController;
use App\Http\Controllers\Api\ClaseZumbaController;
use App\Http\Controllers\Api\InscripcionClaseController;
use App\Http\Controllers\Api\EventoController;
use App\Http\Controllers\Api\TorneoController;
use App\Http\Controllers\Api\EquipoController;
use App\Http\Controllers\Api\PlanMembresiaController;
use App\Http\Controllers\Api\MembresiaClienteController;
use App\Http\Controllers\Api\NotificacionController;
use App\Http\Controllers\chatbot\WhatsappController;
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


//para whatsapp y dialogflow
Route::get('/whatsapp/', [WhatsappController::class, 'verifyToken']);
Route::post('/whatsapp/', [WhatsappController::class, 'escuchar']);


Route::apiResource('clientes', ClienteController::class);
Route::apiResource('canchas', CanchaController::class);
Route::apiResource('areas-zumba', AreaZumbaController::class);
Route::apiResource('instructores', InstructorController::class);
Route::apiResource('reservas', ReservaController::class);
Route::apiResource('clases-zumba', ClaseZumbaController::class);
Route::apiResource('inscripciones-clase', InscripcionClaseController::class);
Route::apiResource('eventos', EventoController::class);
Route::apiResource('torneos', TorneoController::class);
Route::apiResource('equipos', EquipoController::class);
Route::apiResource('planes-membresia', PlanMembresiaController::class);
Route::apiResource('membresias-cliente', MembresiaClienteController::class);
Route::apiResource('notificaciones', NotificacionController::class);

// rutas personalizadas
Route::get('/reservas/obtporfecha/{reservadate}', [ReservaController::class, 'getReservasByDate']);
Route::get('/reservas/cliente/{clienteId}', [ReservaController::class, 'getReservasByCliente']);