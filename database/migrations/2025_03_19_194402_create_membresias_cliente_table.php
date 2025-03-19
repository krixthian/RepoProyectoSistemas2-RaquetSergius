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
        Schema::create('membresias_cliente', function (Blueprint $table) {
            $table->id('membresia_id');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('plan_id');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->foreign('cliente_id')->references('cliente_id')->on('clientes');
            $table->foreign('plan_id')->references('plan_id')->on('planes_membresia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membresias_cliente');
    }
};