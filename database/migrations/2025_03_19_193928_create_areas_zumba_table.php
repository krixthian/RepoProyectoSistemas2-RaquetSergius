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
        Schema::create('areas_zumba', function (Blueprint $table) {
            $table->id('area_id');
            $table->string('nombre');
            $table->integer('capacidad');
            $table->boolean('disponible')->default(true);
            $table->decimal('precio_clase', 10, 2);
            $table->binary('img_horario')->nullable(); // image -> binary() es la opción más cercana en Laravel
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('areas_zumba');
    }
};