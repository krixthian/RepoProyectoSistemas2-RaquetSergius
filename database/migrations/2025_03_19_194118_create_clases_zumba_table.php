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
        Schema::create('clases_zumba', function (Blueprint $table) {
            $table->id('clase_id');
            $table->unsignedBigInteger('area_id');
            $table->unsignedBigInteger('instructor_id');
            $table->dateTime('fecha_hora_inicio');
            $table->dateTime('fecha_hora_fin');
            $table->integer('cupo_maximo');
            $table->integer('cupo_actual')->default(0);
            $table->decimal('precio', 10, 2);
            $table->timestamps();

            $table->foreign('area_id')->references('area_id')->on('areas_zumba');
            $table->foreign('instructor_id')->references('instructor_id')->on('instructores');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clases_zumba');
    }
};