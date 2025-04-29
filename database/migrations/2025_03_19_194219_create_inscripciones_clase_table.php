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
        Schema::create('inscripciones_clase', function (Blueprint $table) {
            $table->id('inscripcion_id'); // int PRIMARY KEY AUTO_INCREMENT
            $table->unsignedBigInteger('clase_id'); // int NOT NULL
            $table->unsignedBigInteger('cliente_id'); // int NOT NULL
            $table->timestamp('fecha_inscripcion')->default(DB::raw('CURRENT_TIMESTAMP')); // timestamp NOT NULL DEFAULT (now())
            $table->string('estado', 50)->default('Inscrito'); // varchar(50) NOT NULL DEFAULT 'Inscrito'
            $table->decimal('monto_pagado', 8, 2)->nullable(); // decimal(8,2)
            $table->string('metodo_pago', 50)->nullable(); // varchar(50)
            $table->timestamp('fecha_pago')->nullable(); // timestamp


            $table->foreign('clase_id')->references('clase_id')->on('clases_zumba');
            $table->foreign('cliente_id')->references('cliente_id')->on('clientes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscripciones_clase');
    }
};