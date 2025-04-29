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
        Schema::create('canje_premios', function (Blueprint $table) {
            // Columnas según BASE DE DATOS.sql
            $table->id('canje_id'); // bigint PRIMARY KEY AUTO_INCREMENT
            $table->unsignedBigInteger('cliente_id'); // int NOT NULL
            $table->unsignedBigInteger('premio_id'); // int NOT NULL
            $table->integer('puntos_utilizados'); // int NOT NULL
            $table->timestamp('fecha_canje')->default(DB::raw('CURRENT_TIMESTAMP')); // timestamp NOT NULL DEFAULT (now())
            $table->string('estado', 50)->default('Realizado'); // varchar(50) NOT NULL DEFAULT 'Realizado'
            $table->unsignedBigInteger('reserva_id')->nullable(); // int
            $table->unsignedBigInteger('inscripcion_clase_id')->nullable(); // int

            // Timestamps
            // $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            // $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->timestamps(); // Alternativa Laravel

            // Claves foráneas según BASE DE DATOS.sql
            $table->foreign('cliente_id')->references('cliente_id')->on('clientes');
            $table->foreign('premio_id')->references('premio_id')->on('premios');
            $table->foreign('reserva_id')->references('reserva_id')->on('reservas');
            $table->foreign('inscripcion_clase_id')->references('inscripcion_id')->on('inscripciones_clase');

            // Índices según BASE DE DATOS.sql
            $table->index(['cliente_id', 'fecha_canje']); // CANJE_PREMIO_index_13
            $table->index('premio_id'); // CANJE_PREMIO_index_14
        });

        // Añadir FK a PUNTOS_LOG ahora que CANJE_PREMIO existe
        Schema::table('PUNTOS_LOG', function (Blueprint $table) {
            $table->foreign('canje_premio_id')->references('canje_id')->on('canje_premios');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Quitar FK de PUNTOS_LOG primero
        Schema::table('PUNTOS_LOG', function (Blueprint $table) {
            // El nombre de la FK puede variar, revisa tu BD o usa dropForeignIdFor
            $table->dropForeign(['canje_premio_id']);
        });

        Schema::dropIfExists('canje_premios');
    }
};