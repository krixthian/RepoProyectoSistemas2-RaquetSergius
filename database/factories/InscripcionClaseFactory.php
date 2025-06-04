<?php

namespace Database\Factories;

use App\Models\InscripcionClase;
use App\Models\Cliente;
use App\Models\ClaseZumba;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; // Para DB::raw si es necesario

class InscripcionClaseFactory extends Factory
{
    protected $model = InscripcionClase::class;

    public function definition()
    {
        // Obtener una clase de zumba aleatoria de las existentes
        $claseZumba = ClaseZumba::inRandomOrder()->first(); //
        if (!$claseZumba) { //
            // Si no hay clases, puedes optar por crear una al vuelo o lanzar una excepción.
            // Para seeders, es mejor asegurar que StaticDataSeeder se ejecute primero.
            throw new \Exception("No hay clases de zumba disponibles en la BD para crear inscripciones de prueba. Ejecuta StaticDataSeeder primero."); //
        }

        // 1. Determinar la fecha de inscripción
        // Simula inscripciones desde hace 3 meses hasta hoy para tener un rango de datos
        $fechaInscripcion = Carbon::instance($this->faker->dateTimeBetween('-3 months', 'now')); //

        // 2. Determinar la fecha_clase coherente
        // La clase es para un día específico de la semana ($claseZumba->diasemama)
        // Encontramos la próxima ocurrencia de ese día de la semana a partir de la fecha de inscripción.
        // O podría ser una fecha pasada si el estado es 'Asistio' o 'Cancelado'.

        $diaSemanaClase = $claseZumba->diasemama; // Ej: 'Lunes', 'Martes', etc.
        $fechaClase = null;
        $estado = $this->faker->randomElement(['Activa', 'Asistio', 'Cancelada']); // Cambiado 'Inscrito' a 'Activa' para consistencia

        $intentos = 0;
        while ($intentos < 100) { // Evitar bucles infinitos
            // Si la inscripción es reciente (últimos 15 días), intentamos que la clase sea futura o reciente pasada.
            // Si la inscripción es más antigua, la clase podría ser más antigua.
            if ($fechaInscripcion->gt(Carbon::now()->subDays(15))) {
                // Para inscripciones recientes, buscamos la próxima ocurrencia del día de la semana
                // a partir de la fecha de inscripción, o un poco antes para estados pasados.
                $fechaReferenciaParaClase = $estado === 'Activa' ? $fechaInscripcion->copy()->startOfDay() : $fechaInscripcion->copy()->subDays($this->faker->numberBetween(0, 14))->startOfDay();
                $fechaClaseTemp = $fechaReferenciaParaClase->copy();
                while (ucfirst($fechaClaseTemp->locale('es_ES')->dayName) !== $diaSemanaClase) {
                    $fechaClaseTemp->addDay();
                }
                // Asegurar que la clase no sea demasiado en el futuro (ej. max 2 semanas desde inscripción si es Activa)
                if ($estado === 'Activa' && $fechaClaseTemp->diffInDays($fechaInscripcion) > 14) {
                    $fechaClaseTemp = $fechaInscripcion->copy();
                    while (ucfirst($fechaClaseTemp->locale('es_ES')->dayName) !== $diaSemanaClase) {
                        $fechaClaseTemp->addDay(); // Buscar la primera ocurrencia
                    }
                    // Si aun asi es muy lejana, tomar la primera ocurrencia despues de hoy.
                    if ($fechaClaseTemp->gt(Carbon::today()->addDays(14))) {
                        $fechaClaseTemp = Carbon::today();
                        while (ucfirst($fechaClaseTemp->locale('es_ES')->dayName) !== $diaSemanaClase) {
                            $fechaClaseTemp->addDay();
                        }
                    }
                }
                $fechaClase = $fechaClaseTemp;

            } else { // Inscripciones más antiguas
                $fechaClase = Carbon::instance($this->faker->dateTimeBetween($fechaInscripcion->copy()->subDays(7), $fechaInscripcion->copy()->addDays(7)));
                while (ucfirst($fechaClase->locale('es_ES')->dayName) !== $diaSemanaClase) {
                    $fechaClase->addDay(); // Ajustar al día correcto más cercano
                }
            }

            // Ajustar estado basado en la fecha de la clase
            if ($fechaClase->lt(Carbon::today())) { // Si la clase ya pasó
                if ($estado === 'Activa') { // Si era 'Activa' y ya pasó, la ponemos como 'Asistio'
                    $estado = 'Asistio';
                }
            } elseif ($estado === 'Asistio') { // Si es 'Asistio' pero la fecha es futura, no tiene sentido, la cambiamos a 'Activa'
                $estado = 'Activa';
            }

            break; // Salir del while una vez que tenemos una fecha coherente
            $intentos++;
        }
        if (!$fechaClase) { // Fallback si el while no funcionó
            $fechaClase = Carbon::today();
            while (ucfirst($fechaClase->locale('es_ES')->dayName) !== $diaSemanaClase) {
                $fechaClase->addDay();
            }
        }


        // 3. Determinar fecha_pago y fecha_cancelacion
        $fechaPago = null;
        $fechaCancelacion = null;

        if ($estado === 'Activa' || $estado === 'Asistio') {
            // 90% de probabilidad de que esté pagado, en la fecha de inscripción o poco después
            if ($this->faker->boolean(90)) {
                $fechaPago = $this->faker->dateTimeBetween($fechaInscripcion, $fechaInscripcion->copy()->addDays(2));
            }
        } elseif ($estado === 'Cancelada') {
            // Si se canceló, pudo o no haber sido pagada.
            if ($this->faker->boolean(30)) { // 30% de probabilidad de que haya pagado antes de cancelar
                $fechaPago = $this->faker->dateTimeBetween($fechaInscripcion, $fechaInscripcion->copy()->addDays(2));
            }
            // La fecha de cancelación debe ser después de la inscripción y antes o en la fecha de la clase
            $limiteSuperiorCancelacion = $fechaClase->copy()->startOfDay();
            if ($fechaInscripcion->lt($limiteSuperiorCancelacion)) {
                $fechaCancelacion = $this->faker->dateTimeBetween($fechaInscripcion, $limiteSuperiorCancelacion);
            } else { // Si la inscripción fue el mismo día de la clase (o después, lo que no debería pasar con la lógica anterior)
                $fechaCancelacion = $fechaInscripcion;
            }
        }


        return [
            'cliente_id' => Cliente::factory(), //
            'clase_id' => $claseZumba->clase_id, //
            'fecha_inscripcion' => $fechaInscripcion->format('Y-m-d H:i:s'),
            'fecha_clase' => $fechaClase->format('Y-m-d'), // NUEVO CAMPO
            'estado' => $estado,
            'monto_pagado' => ($fechaPago && $estado !== 'Cancelada') ? $claseZumba->precio : ($fechaPago && $estado === 'Cancelada' && $this->faker->boolean(20) ? $claseZumba->precio : null), // Puede que no haya pagado si canceló, o que se haya reembolsado (no modelado)
            'metodo_pago' => $fechaPago ? $this->faker->randomElement(['Efectivo', 'Transferencia', 'QR']) : null, //
            'fecha_pago' => $fechaPago ? Carbon::instance($fechaPago)->format('Y-m-d H:i:s') : null,
            'fecha_cancelacion' => $fechaCancelacion ? Carbon::instance($fechaCancelacion)->format('Y-m-d H:i:s') : null, // NUEVO CAMPO
        ];
    }
}