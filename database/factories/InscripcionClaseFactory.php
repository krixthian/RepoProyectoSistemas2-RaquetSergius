<?php

namespace Database\Factories;

use App\Models\InscripcionClase;
use App\Models\Cliente;
use App\Models\ClaseZumba; // Asegúrate que el modelo ClaseZumba esté importado
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class InscripcionClaseFactory extends Factory
{
    protected $model = InscripcionClase::class;

    public function definition()
    {
        // Obtener una clase de zumba aleatoria de las existentes (creadas por StaticDataSeeder)
        $claseZumba = ClaseZumba::inRandomOrder()->first();
        if (!$claseZumba) {
            throw new \Exception("No hay clases de zumba disponibles en la BD para crear inscripciones de prueba. Ejecuta StaticDataSeeder primero.");
        }

        // Simular fecha de inscripción relativa a la fecha actual, o a la fecha de creación del cliente
        $fechaInscripcion = $this->faker->dateTimeBetween('-1 year', 'now');

        return [
            'cliente_id' => Cliente::factory(),
            'clase_id' => $claseZumba->clase_id, // 'clase_id' según la migración de inscripciones_clase
            'fecha_inscripcion' => Carbon::instance($fechaInscripcion)->format('Y-m-d H:i:s'),
            'estado' => $this->faker->randomElement(['Inscrito', 'Asistió', 'Cancelado']), // 'estado' según migración
            'monto_pagado' => $claseZumba->precio, // Usar el 'precio' del modelo ClaseZumba
            'metodo_pago' => $this->faker->randomElement(['Efectivo', 'Transferencia', 'QR']),
            'fecha_pago' => $this->faker->boolean(80) ? Carbon::instance($fechaInscripcion)->format('Y-m-d H:i:s') : null,
            // No hay created_at/updated_at porque $timestamps = false en el modelo InscripcionClase
        ];
    }
}