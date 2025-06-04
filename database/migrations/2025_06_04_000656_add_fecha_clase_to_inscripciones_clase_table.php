<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inscripciones_clase', function (Blueprint $table) {
            $table->date('fecha_clase')->after('fecha_inscripcion')->comment('Fecha específica de la clase a la que se inscribe');
            // Opcional: si la hora de inicio puede variar para una misma clase_id en diferentes fechas
            // $table->time('hora_inicio_clase')->after('fecha_clase')->nullable()->comment('Hora específica de inicio si puede variar');
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones_clase', function (Blueprint $table) {
            $table->dropColumn('fecha_clase');
            // $table->dropColumn('hora_inicio_clase');
        });
    }
};