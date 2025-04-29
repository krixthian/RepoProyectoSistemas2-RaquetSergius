<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Necesario para DB::raw

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('puntos_log', function (Blueprint $table) {
            // Columnas según BASE DE DATOS.sql
            $table->id('log_id'); // bigint PRIMARY KEY AUTO_INCREMENT
            $table->unsignedBigInteger('cliente_id'); // int NOT NULL
            $table->string('accion', 100); // varchar(100) NOT NULL
            $table->integer('puntos_cambio'); // int NOT NULL
            $table->integer('puntos_antes'); // int NOT NULL
            $table->integer('puntos_despues'); // int NOT NULL
            $table->unsignedBigInteger('reserva_id')->nullable(); // int
            $table->unsignedBigInteger('inscripcion_clase_id')->nullable(); // int
            $table->unsignedBigInteger('encuesta_id')->nullable(); // int
            $table->unsignedBigInteger('canje_premio_id')->nullable(); // int
            $table->string('detalle', 255)->nullable(); // varchar(255)
            $table->timestamp('fecha')->default(DB::raw('CURRENT_TIMESTAMP')); // timestamp NOT NULL DEFAULT (now())

            // No usar timestamps() de Laravel si ya definiste 'fecha' y no necesitas 'updated_at'

            // Claves foráneas según BASE DE DATOS.sql
            $table->foreign('cliente_id')->references('cliente_id')->on('clientes');
            $table->foreign('reserva_id')->references('reserva_id')->on('reservas');
            $table->foreign('inscripcion_clase_id')->references('inscripcion_id')->on('inscripciones_clase');
            $table->foreign('encuesta_id')->references('encuesta_id')->on('encuestas');
            // $table->foreign('canje_premio_id')->references('canje_id')->on('CANJE_PREMIO'); // Definir CANJE_PREMIO antes

            // Índices según BASE DE DATOS.sql
            $table->index(['cliente_id', 'fecha']); // PUNTOS_LOG_index_11
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puntos_log');
    }
};