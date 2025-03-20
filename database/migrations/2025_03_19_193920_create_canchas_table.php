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
        Schema::create('canchas', function (Blueprint $table) {
            $table->id('cancha_id');
            $table->string('nombre');
            $table->string('tipo'); // "wally"
            $table->boolean('disponible')->default(true);
            $table->decimal('precio_hora', 10, 2);
            $table->integer('capacidad');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('canchas');
    }
};