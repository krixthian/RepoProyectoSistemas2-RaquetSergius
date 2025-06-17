<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clases_zumba', function (Blueprint $table) {
            $table->id('clase_id'); // int PRIMARY KEY AUTO_INCREMENT
            $table->unsignedBigInteger('area_id'); // int NOT NULL
            $table->unsignedBigInteger('instructor_id'); // int NOT NULL
            $table->string('diasemama', 10)->nullable(); // varchar(10) - ¿Quizás 'dia_semana'?
            $table->time('hora_inicio'); // time NOT NULL
            $table->time('hora_fin'); // time NOT NULL
            $table->decimal('precio', 8, 2); // decimal(8,2) NOT NULL
            $table->integer('cupo_maximo'); // int NOT NULL
            $table->boolean('habilitado')->nullable()->default(true); // boolean

            $table->timestamps();

            $table->foreign('area_id')->references('area_id')->on('areas_zumba'); 
            $table->foreign('instructor_id')->references('instructor_id')->on('instructores'); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clases_zumba'); 
    }
};

