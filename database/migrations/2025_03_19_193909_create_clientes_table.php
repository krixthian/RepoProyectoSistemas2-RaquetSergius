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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id('cliente_id');
            $table->string('nombre');
            $table->string('telefono');
            $table->string('email')->nullable();
            $table->boolean('cliente_frecuente')->default(false);
            $table->date('fecha_registro');

            // Columnas adicionales del SQL
            $table->timestamp('last_activity_at')->nullable(); // timestamp
            $table->boolean('is_churned')->default(false); // boolean NOT NULL DEFAULT false
            $table->integer('puntos')->default(0); // int NOT NULL DEFAULT 0



            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};