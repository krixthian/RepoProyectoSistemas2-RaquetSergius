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
        Schema::create('encuestas', function (Blueprint $table) {
            // Columnas según BASE DE DATOS.sql
            $table->id('encuesta_id'); // int PRIMARY KEY AUTO_INCREMENT
            $table->unsignedBigInteger('cliente_id'); // int NOT NULL
            $table->unsignedBigInteger('reserva_id')->nullable(); // int
            $table->unsignedBigInteger('inscripcion_clase_id')->nullable(); // int
            $table->integer('puntuacion_general')->nullable(); // int
            $table->text('comentario_general')->nullable(); // text
            $table->integer('puntuacion_limpieza')->nullable(); // int
            $table->integer('puntuacion_instructor')->nullable(); // int
            $table->timestamp('fecha_envio_invitacion')->nullable(); // timestamp
            $table->timestamp('fecha_completada')->nullable(); // timestamp

            // Timestamps de Laravel (created_at, updated_at) - Ajusta si prefieres los defaults del SQL
            // $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            // $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->timestamps(); // Alternativa Laravel

            // Claves foráneas según BASE DE DATOS.sql
            $table->foreign('cliente_id')->references('cliente_id')->on('clientes'); // Asume que la tabla clientes se llama CLIENTE
            $table->foreign('reserva_id')->references('reserva_id')->on('reservas'); // Asume que la tabla reservas se llama RESERVA
            $table->foreign('inscripcion_clase_id')->references('inscripcion_id')->on('inscripciones_clase'); // Asume que la tabla inscripciones se llama INSCRIPCION_CLASE

            // Índices según BASE DE DATOS.sql
            $table->index(['cliente_id', 'fecha_completada']); // ENCUESTA_index_8
            $table->unique('reserva_id'); // ENCUESTA_index_9
            $table->unique('inscripcion_clase_id'); // ENCUESTA_index_10
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encuestas');
    }
};