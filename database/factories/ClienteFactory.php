<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition()
    {
        $endDate = Carbon::now();
        $startDateCreation = $endDate->copy()->subYears(2); // Clientes creados en los últimos 2 años
        $createdAt = Carbon::instance($this->faker->dateTimeBetween($startDateCreation, $endDate));

        $lastActivity = null;

        // Decidir si este cliente tendrá una last_activity_at (85% de probabilidad)
        if ($this->faker->boolean(85)) {
            // De estos, 40% tendrán una última actividad deliberadamente antigua (churneable)
            if ($this->faker->boolean(40)) {
                // Asegurar que la fecha sea realmente > 2 meses atrás y < 1 año atrás (para variación)
                // y que sea después de su fecha de creación.
                $maxOldDate = Carbon::now()->subMonths(2)->subDay(); // Un día antes del límite de churn
                $minOldDate = Carbon::now()->subYear()->subDay(); // Un día antes del límite de 1 año

                if ($maxOldDate->lt($createdAt)) { // Si el cliente es muy nuevo para ser churneado "antiguamente"
                    $lastActivity = $createdAt; // Su última actividad es su creación
                } else {
                    // Asegurar que minOldDate no sea antes de createdAt
                    $actualMinOldDate = $minOldDate->gt($createdAt) ? $minOldDate : $createdAt->copy()->addDay();

                    if ($actualMinOldDate->gte($maxOldDate)) { // Rango inválido, suele pasar si cliente es reciente
                        $lastActivity = $createdAt;
                    } else {
                        $lastActivity = Carbon::instance($this->faker->dateTimeBetween($actualMinOldDate, $maxOldDate));
                    }
                }
            } else {
                // Actividad reciente (dentro de los últimos 2 meses), pero después de la creación
                $minRecentDate = Carbon::now()->subMonths(2);
                // Asegurar que minRecentDate no sea antes de createdAt
                $actualMinRecentDate = $minRecentDate->gt($createdAt) ? $minRecentDate : $createdAt;

                if ($actualMinRecentDate->gte(Carbon::now())) { // Si el rango es inválido (cliente muy reciente)
                    $lastActivity = Carbon::instance($this->faker->dateTimeBetween($createdAt, Carbon::now()));
                } else {
                    $lastActivity = Carbon::instance($this->faker->dateTimeBetween($actualMinRecentDate, Carbon::now()));
                }
            }
        }


        $nombre = $this->faker->firstName;
        return [
            'cliente_id' => null, // Como acordamos
            'nombre' => $nombre,
            'email' => $this->faker->unique()->safeEmail,
            'telefono' => $this->faker->unique()->numerify('591########'), // Formato Bolivia
            'fecha_registro' => $createdAt,
            'puntos' => $this->faker->numberBetween(0, 500), // Campo 'puntos' de la migración de clientes
            'last_activity_at' => $lastActivity ? $lastActivity->format('Y-m-d H:i:s') : null,
            'is_churned' => false, // La tarea de churn lo actualizará
            'created_at' => $createdAt,
            'updated_at' => $this->faker->dateTimeBetween($createdAt, $endDate),
        ];
    }
}