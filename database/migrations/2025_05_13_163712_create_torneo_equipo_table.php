<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('torneo_equipo', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('torneo_id')->nullable()->constrained('torneos', 'torneo_id')->onDelete('cascade');
            // Reemplaza 'nombre_de_columna_referenciada' por el nombre real de la columna primaria en la tabla 'torneos'
            $table->foreignId('equipo_id')->constrained('equipos', 'equipo_id')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['torneo_id', 'equipo_id']); // Evita duplicados
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('torneo_equipo');
    }
};