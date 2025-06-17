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
        Schema::create('reservas', function (Blueprint $table) {
            $table->id('reserva_id'); 
            $table->unsignedBigInteger('cancha_id'); 
            $table->unsignedBigInteger('cliente_id'); 
            $table->date('fecha'); 
            $table->time('hora_inicio'); 
            $table->time('hora_fin'); 
            $table->decimal('monto', 10, 2);
            $table->string('estado', 50)->default('Pendiente de Pago'); 
            $table->decimal('monto_total', 8, 2)->nullable();
            $table->string('ruta_comprobante_pago')->nullable();

            $table->string('metodo_pago'); // "QR/efectivo"
            $table->boolean('pago_completo')->default(false);

            // Esto se encarga de created_at y updated_at automáticamente
            $table->timestamps();

            $table->foreign('cancha_id')->references('cancha_id')->on('canchas'); 
            $table->foreign('cliente_id')->references('cliente_id')->on('clientes'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas'); 
    }
};

