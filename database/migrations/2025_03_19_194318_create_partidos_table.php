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
        Schema::create('partidos', function (Blueprint $table) {
            $table->id('partido_id');
            $table->unsignedBigInteger('torneo_id');
            $table->unsignedBigInteger('equipo1_id');
            $table->unsignedBigInteger('equipo2_id');
            $table->unsignedBigInteger('cancha_id');
            $table->dateTime('fecha_hora');
            $table->string('resultado')->nullable();
            $table->string('estado'); // "programado/finalizado/cancelado"
            $table->timestamps();

            $table->foreign('torneo_id')->references('torneo_id')->on('torneos');
            $table->foreign('equipo1_id')->references('equipo_id')->on('equipos');
            $table->foreign('equipo2_id')->references('equipo_id')->on('equipos');
            $table->foreign('cancha_id')->references('cancha_id')->on('canchas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partidos');
    }
};