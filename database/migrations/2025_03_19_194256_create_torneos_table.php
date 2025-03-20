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
        Schema::create('torneos', function (Blueprint $table) {
            $table->id('torneo_id');
            $table->unsignedBigInteger('evento_id');
            $table->string('categoria');
            $table->integer('num_equipos');
            $table->string('estado'); // "programado/en curso/finalizado"
            $table->string('deporte'); // "wally/zumba"
            $table->timestamps();

            $table->foreign('evento_id')->references('evento_id')->on('eventos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('torneos');
    }
};