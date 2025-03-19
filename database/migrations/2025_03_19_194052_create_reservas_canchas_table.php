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
        Schema::create('reservas_canchas', function (Blueprint $table) {
            $table->id('reserva_cancha_id');
            $table->unsignedBigInteger('reserva_id');
            $table->unsignedBigInteger('cancha_id');
            $table->decimal('precio_total', 10, 2);
            $table->timestamps();

            $table->foreign('reserva_id')->references('reserva_id')->on('reservas')->onDelete('cascade');
            $table->foreign('cancha_id')->references('cancha_id')->on('canchas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas_canchas');
    }
};