<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inscripciones_clase', function (Blueprint $table) {
            $table->id('inscripcion_id');
            $table->unsignedBigInteger('clase_id');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('reserva_id');
            $table->dateTime('fecha_inscripcion');
            $table->boolean('asistio')->nullable();
            $table->timestamps();

            $table->foreign('clase_id')->references('clase_id')->on('clases_zumba');
            $table->foreign('cliente_id')->references('cliente_id')->on('clientes');
            $table->foreign('reserva_id')->references('reserva_id')->on('reservas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscripciones_clase');
    }
};