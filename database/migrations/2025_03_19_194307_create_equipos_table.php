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
        Schema::create('equipos', function (Blueprint $table) {
            $table->id('equipo_id');
            $table->string('nombre');
            $table->unsignedBigInteger('torneo_id');
            $table->unsignedBigInteger('capitan_id');
            $table->timestamps();

            $table->foreign('torneo_id')->references('torneo_id')->on('torneos');
            $table->foreign('capitan_id')->references('cliente_id')->on('clientes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};