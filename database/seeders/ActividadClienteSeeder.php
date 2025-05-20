<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cliente;
use App\Models\Cancha;
use App\Models\ClaseZumba;
use App\Models\Reserva;
use App\Models\InscripcionClase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Faker\Factory as FakerFactory; // Importar Faker

class ActividadClienteSeeder extends Seeder
{
    protected $faker; // Propiedad para Faker

    public function __construct()
    {
        $this->faker = FakerFactory::create(); // Inicializar Faker
    }

    public function run()
    {
        $this->command->info('Iniciando generación de actividad de clientes...');
        Log::info('[ActividadClienteSeeder] Iniciando.');

        $clientes = Cliente::all();
        $canchas = Cancha::all();
        $clasesZumba = ClaseZumba::all();
        $now = Carbon::now();
        $churnCutoffDate = $now->copy()->subMonths(2); // Límite para considerar actividad "reciente"

        if ($canchas->isEmpty() || $clasesZumba->isEmpty() || $clientes->isEmpty()) {
            $this->command->error('Faltan datos maestros (clientes, canchas o clases) para generar actividad.');
            Log::error('[ActividadClienteSeeder] Faltan datos maestros.');
            return;
        }

        $totalActividadesGeneradas = 0;

        foreach ($clientes as $cliente) {
            $probabilidadDeNuevaActividad = 70; // 70% de los clientes tendrán nuevas actividades generadas aquí
            $actividadMasRecienteCliente = $cliente->last_activity_at ? Carbon::parse($cliente->last_activity_at) : null;

            // Si el cliente ya tiene una last_activity_at "reciente" (gracias al factory),
            // o si decidimos no darle nueva actividad, lo saltamos.
            if (($actividadMasRecienteCliente && $actividadMasRecienteCliente->gte($churnCutoffDate)) || !$this->faker->boolean($probabilidadDeNuevaActividad)) {
                // Si se salta, pero su last_activity_at es del futuro (imposible por factory pero por si acaso)
                if ($actividadMasRecienteCliente && $actividadMasRecienteCliente->isFuture()) {
                    $cliente->last_activity_at = $now->copy()->subDays(rand(1, 60)); // Ajustar a pasado reciente
                    $cliente->save();
                }
                continue;
            }

            // El cliente o no tiene actividad, o es antigua, o fue seleccionado para nueva actividad.
            // Definir el rango para generar actividades nuevas.
            // Las nuevas actividades deben ser DESPUÉS de la última actividad conocida o creación.
            $fechaDesdeParaNuevasActividades = $actividadMasRecienteCliente ? $actividadMasRecienteCliente->copy()->addDay() : Carbon::parse($cliente->created_at);
            $fechaHastaParaNuevasActividades = $now;

            // Asegurar que el rango sea válido
            if ($fechaDesdeParaNuevasActividades->gte($fechaHastaParaNuevasActividades)) {
                if ($actividadMasRecienteCliente && $actividadMasRecienteCliente->isFuture()) { // Corregir si last_activity_at es del futuro
                    $cliente->last_activity_at = $now->copy()->subDays(rand(1, 30));
                    $cliente->save();
                }
                continue; // No se puede generar actividad si la fecha de inicio es hoy o en el futuro
            }

            $cantidadNuevasActividades = $this->faker->numberBetween(1, 5); // Generar pocas actividades nuevas para no siempre refrescar a "muy reciente"

            for ($i = 0; $i < $cantidadNuevasActividades; $i++) {
                $fechaActividad = Carbon::createFromTimestamp(
                    $this->faker->dateTimeBetween($fechaDesdeParaNuevasActividades, $fechaHastaParaNuevasActividades)->getTimestamp()
                );

                // Asegurar que la nueva fecha de actividad no sea anterior a la última real
                if ($actividadMasRecienteCliente && $fechaActividad->lt($actividadMasRecienteCliente)) {
                    $fechaActividad = $actividadMasRecienteCliente->copy()->addMicroseconds(1); // Un poquito después
                }
                if ($fechaActividad->gt($now)) { // No generar actividad en el futuro
                    $fechaActividad = $now;
                }


                $tipoActividad = $this->faker->randomElement(['reserva', 'zumba']);

                if ($tipoActividad === 'reserva') {
                    $canchaSeleccionada = $canchas->random();
                    $horaInicioNum = $this->faker->numberBetween(config('configuraciones.hora_apertura_club', 7), config('configuraciones.hora_cierre_club', 22) - 1);
                    $horaInicio = Carbon::createFromTime($horaInicioNum, $this->faker->randomElement([0, 30]), 0);

                    Reserva::factory()->create([
                        'cliente_id' => $cliente->cliente_id,
                        'cancha_id' => $canchaSeleccionada->cancha_id,
                        'fecha' => $fechaActividad->format('Y-m-d'),
                        'hora_inicio' => $horaInicio->format('H:i:s'),
                        'hora_fin' => $horaInicio->copy()->addHour()->format('H:i:s'), // Asumimos 1h
                        'monto' => $canchaSeleccionada->precio_hora,
                        'estado' => $fechaActividad->isPast() ? $this->faker->randomElement(['Completada', 'No Asistio']) : 'Confirmada',
                        'pago_completo' => $this->faker->boolean(80), // 80% de probabilidad de que el pago esté completo
                        'created_at' => $fechaActividad->copy()->subDays(rand(0, 2)), // Reserva hecha poco antes
                        'updated_at' => $fechaActividad->copy()->subDays(rand(0, 1)),
                    ]);
                    if (is_null($actividadMasRecienteCliente) || $fechaActividad->gt($actividadMasRecienteCliente)) {
                        $actividadMasRecienteCliente = $fechaActividad;
                    }
                    $totalActividadesGeneradas++;

                } elseif ($tipoActividad === 'zumba') {
                    $claseSeleccionada = $clasesZumba->random();
                    InscripcionClase::factory()->create([
                        'cliente_id' => $cliente->cliente_id,
                        'clase_id' => $claseSeleccionada->clase_id,
                        'fecha_inscripcion' => $fechaActividad->format('Y-m-d H:i:s'),
                        'estado' => $fechaActividad->lte($now) ? $this->faker->randomElement(['Asistió', 'Cancelado']) : 'Inscrito',
                        'monto_pagado' => $claseSeleccionada->precio,
                    ]);
                    if (is_null($actividadMasRecienteCliente) || $fechaActividad->gt($actividadMasRecienteCliente)) {
                        $actividadMasRecienteCliente = $fechaActividad;
                    }
                    $totalActividadesGeneradas++;
                }
            }

            // Actualizar el last_activity_at del cliente SOLO si se generaron nuevas actividades para él
            if ($actividadMasRecienteCliente && (!$cliente->last_activity_at || $actividadMasRecienteCliente->gt(Carbon::parse($cliente->last_activity_at)))) {
                if ($actividadMasRecienteCliente->isFuture()) { // Doble check para no guardar fechas futuras
                    $cliente->last_activity_at = $now;
                } else {
                    $cliente->last_activity_at = $actividadMasRecienteCliente;
                }
                $cliente->is_churned = false; // Si hubo actividad, no está en churn
                $cliente->save();
            }
        }
        $this->command->info("Generación de actividad de clientes completada. Total actividades: {$totalActividadesGeneradas}");
        Log::info("[ActividadClienteSeeder] Finalizado. Total actividades generadas: {$totalActividadesGeneradas}.");
    }
}