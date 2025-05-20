<?php

namespace Database\Factories;

use App\Models\Reserva;
use App\Models\Cliente;
use App\Models\Cancha; // Asegúrate que el modelo Cancha esté importado
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class ReservaFactory extends Factory
{
    protected $model = Reserva::class;

    public function definition()
    {
        $fechaReserva = $this->faker->dateTimeBetween('-1 year', '+1 month'); // Rango para fecha_reserva
        $horaInicioNum = $this->faker->numberBetween(7, 21); // Horas de operación
        $horaInicio = Carbon::createFromTime($horaInicioNum, $this->faker->randomElement([0, 30]), 0);
        $horaFin = $horaInicio->copy()->addHour(); // Asumimos 1 hora de reserva

        // Obtener una cancha aleatoria de las existentes (creadas por StaticDataSeeder)
        $cancha = Cancha::inRandomOrder()->first();
        // Si no hay canchas (improbable si StaticDataSeeder se ejecutó), crear una como fallback es una opción,
        // pero es mejor asegurar que StaticDataSeeder se ejecute primero.
        if (!$cancha) {
            throw new \Exception("No hay canchas disponibles en la BD para crear reservas de prueba. Ejecuta StaticDataSeeder primero.");
        }

        return [
            'cliente_id' => Cliente::factory(), // Crea un nuevo cliente o usa uno existente si lo pasas al llamar al factory
            'cancha_id' => $cancha->cancha_id,
            'fecha' => Carbon::instance($fechaReserva)->format('Y-m-d'),
            'hora_inicio' => $horaInicio->format('H:i:s'),
            'hora_fin' => $horaFin->format('H:i:s'),
            'monto' => $cancha->precio_hora, // 'monto' según la migración de reservas
            'estado' => $this->faker->randomElement(['Confirmada', 'Completada', 'Cancelada', 'No Asistio']), // 'estado' según migración
            'metodo_pago' => $this->faker->randomElement(['Efectivo', 'Transferencia', 'QR']),
            'pago_completo' => $this->faker->boolean(80), // 80% de probabilidad de que el pago esté completo
            'created_at' => Carbon::instance($fechaReserva)->subDays($this->faker->numberBetween(1, 30)),
            'updated_at' => Carbon::instance($fechaReserva)->subDays($this->faker->numberBetween(0, 29)),
        ];
    }
}