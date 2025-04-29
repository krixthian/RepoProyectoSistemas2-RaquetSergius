<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Necesario para DB::raw

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('premios', function (Blueprint $table) {
            // Columnas según BASE DE DATOS.sql
            $table->id('premio_id'); // int PRIMARY KEY AUTO_INCREMENT
            $table->string('nombre', 255); // varchar(255) NOT NULL
            $table->text('descripcion')->nullable(); // text
            $table->integer('puntos_requeridos'); // int NOT NULL
            $table->string('tipo', 50); // varchar(50) NOT NULL
            $table->decimal('valor_descuento', 8, 2)->nullable(); // decimal(8,2)
            $table->decimal('porcentaje_descuento', 5, 2)->nullable(); // decimal(5,2)
            $table->unsignedBigInteger('clase_gratis_id')->nullable(); // int
            $table->string('producto_nombre', 255)->nullable(); // varchar(255)
            $table->boolean('activo')->default(true); // boolean NOT NULL DEFAULT true
            $table->integer('stock')->nullable(); // int
            $table->date('valido_desde')->nullable(); // date
            $table->date('valido_hasta')->nullable(); // date

            // Timestamps
            // $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            // $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->timestamps(); // Alternativa Laravel

            // Claves foráneas según BASE DE DATOS.sql
            $table->foreign('clase_gratis_id')->references('clase_id')->on('clases_zumba'); // Asume que la tabla clases se llama CLASE_ZUMBA

            // Índices según BASE DE DATOS.sql
            $table->index(['activo', 'puntos_requeridos']); // PREMIOS_index_12
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('premios');
    }
};