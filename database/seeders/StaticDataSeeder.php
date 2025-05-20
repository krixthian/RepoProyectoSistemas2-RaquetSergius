<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Instructor; // Necesitarás el modelo Instructor

class StaticDataSeeder extends Seeder
{
    public function run(): void
    {
        // Desactivar chequeo de claves foráneas temporalmente si es necesario
        // DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Vaciar tablas si quieres que este seeder sea la única fuente de estos datos estáticos
        DB::table('inscripciones_clase')->delete(); // Borrar dependientes primero
        DB::table('reservas')->delete();            // Borrar dependientes primero
        DB::table('clases_zumba')->delete();
        DB::table('areas_zumba')->delete();
        DB::table('canchas')->delete();
        DB::table('instructores')->delete(); // Si también los manejas aquí

        // 1. Instructores (Si son estáticos o pocos)
        // Si tienes muchos o quieres variabilidad, usa un InstructorFactory y un InstructorSeeder separado.
        // Por ahora, los insertamos aquí como ejemplo.
        Instructor::create([
            'instructor_id' => 1, // Asegúrate que la PK sea auto-incremental o provee el ID si no lo es
            'nombre' => 'Lucia',
            'especialidad' => 'Zumba Master Class',
            'telefono' => '59170000001',
            'tarifa_hora' => 20.00,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        Instructor::create([
            'instructor_id' => 2,
            'nombre' => 'Carlos',
            'especialidad' => 'Zumba Fitness',
            'telefono' => '59170000002',
            'tarifa_hora' => 20.00,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        Instructor::create([
            'instructor_id' => 3,
            'nombre' => 'Ana',
            'especialidad' => 'Zumba Gold',
            'telefono' => '59170000003',
            'tarifa_hora' => 20.00,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        $this->command->info('Instructores estáticos creados.');

        // 2. Área de Zumba (Solo 1)
        // Tu INSERT tiene columnas nombre, capacidad, disponible, precio_clase, ruta_imagen
        // La migración tiene nombre_area, capacidad_maxima, descripcion, equipamiento_disponible, estado, habilitado
        // Voy a usar los nombres de la migración areas_zumba
        // y el modelo AreaZumba
        DB::table('areas_zumba')->insert([
            'area_id' => 1, // Si no es auto-incremental
            'nombre' => 'Salón Principal de Zumba', // 'nombre_area' en migración
            'capacidad' => 30, // 'capacidad_maxima' en migración
            'disponible' => true,      // 'habilitado' en migración
            'precio_clase' => 10.00, // 'precio_clase' en migración
            'ruta_imagen' => 'image/horarios_zumba.jpg',
            // Faltarían campos como ruta_imagen si los tienes en la tabla final,
            // pero no están en la migración que proporcionaste.
            // Tu INSERT menciona 'ruta_imagen' y 'precio_clase' para area_zumba,
            // pero esos campos están en 'clases_zumba' o no están en la migración de 'areas_zumba'.
            // Ajustaré según la migración `areas_zumba`.
        ]);
        $this->command->info('Área de Zumba estática creada.');

        // 3. Clases de Zumba (Horario Semanal)
        // Tu INSERT tiene area_id, instructor_id, diasemama, hora_inicio, hora_fin, precio, cupo_maximo, habilitado
        // La migración de clases_zumba tiene:
        // instructor_id, area_id, diasemama, hora_inicio, hora_fin, precio_clase, cupos_disponibles, estado, habilitado
        // El modelo ClaseZumba usa:
        // instructor_id, area_id, diasemama, hora_inicio, hora_fin, precio, cupo_maximo, habilitado
        // Usaré los del MODELO.
        $clases = [
            ['area_id' => 1, 'instructor_id' => 3, 'diasemama' => 'Lunes', 'hora_inicio' => '08:30', 'hora_fin' => '09:30', 'precio' => 20.00, 'cupo_maximo' => 30, 'habilitado' => true],
            ['area_id' => 1, 'instructor_id' => 3, 'diasemama' => 'Martes', 'hora_inicio' => '08:30', 'hora_fin' => '09:30', 'precio' => 20.00, 'cupo_maximo' => 30, 'habilitado' => true],
            ['area_id' => 1, 'instructor_id' => 3, 'diasemama' => 'Jueves', 'hora_inicio' => '08:30', 'hora_fin' => '09:30', 'precio' => 20.00, 'cupo_maximo' => 30, 'habilitado' => true],
            ['area_id' => 1, 'instructor_id' => 3, 'diasemama' => 'Viernes', 'hora_inicio' => '08:30', 'hora_fin' => '09:30', 'precio' => 20.00, 'cupo_maximo' => 30, 'habilitado' => true],
            ['area_id' => 1, 'instructor_id' => 1, 'diasemama' => 'Lunes', 'hora_inicio' => '16:30', 'hora_fin' => '17:30', 'precio' => 20.00, 'cupo_maximo' => 30, 'habilitado' => true],
            ['area_id' => 1, 'instructor_id' => 1, 'diasemama' => 'Miércoles', 'hora_inicio' => '16:30', 'hora_fin' => '17:30', 'precio' => 20.00, 'cupo_maximo' => 30, 'habilitado' => true],
            ['area_id' => 1, 'instructor_id' => 1, 'diasemama' => 'Viernes', 'hora_inicio' => '16:30', 'hora_fin' => '17:30', 'precio' => 20.00, 'cupo_maximo' => 30, 'habilitado' => true],
            ['area_id' => 1, 'instructor_id' => 1, 'diasemama' => 'Jueves', 'hora_inicio' => '17:30', 'hora_fin' => '18:30', 'precio' => 20.00, 'cupo_maximo' => 30, 'habilitado' => true],
            ['area_id' => 1, 'instructor_id' => 1, 'diasemama' => 'Lunes', 'hora_inicio' => '18:00', 'hora_fin' => '19:00', 'precio' => 20.00, 'cupo_maximo' => 30, 'habilitado' => true],
            ['area_id' => 1, 'instructor_id' => 1, 'diasemama' => 'Miércoles', 'hora_inicio' => '18:00', 'hora_fin' => '19:00', 'precio' => 20.00, 'cupo_maximo' => 30, 'habilitado' => true],
            ['area_id' => 1, 'instructor_id' => 1, 'diasemama' => 'Viernes', 'hora_inicio' => '18:00', 'hora_fin' => '19:00', 'precio' => 20.00, 'cupo_maximo' => 30, 'habilitado' => true],
            // Tu última clase tiene hora_fin '28:00', lo cual es inválido. Asumiré '20:00'.
            ['area_id' => 1, 'instructor_id' => 1, 'diasemama' => 'Viernes', 'hora_inicio' => '19:00', 'hora_fin' => '20:00', 'precio' => 20.00, 'cupo_maximo' => 30, 'habilitado' => true],
        ];

        foreach ($clases as $clase) {
            DB::table('clases_zumba')->insert(array_merge($clase, ['created_at' => Carbon::now(), 'updated_at' => Carbon::now()]));
        }
        $this->command->info('Clases de Zumba estáticas creadas.');


        // 4. Canchas (Solo 3)
        // Tu INSERT tiene cancha_id, nombre, tipo, disponible, precio_hora, capacidad
        // La migración de canchas tiene:
        // nombre_cancha, tipo_cancha, descripcion, precio_hora, estado, habilitado
        // El modelo Cancha usa:
        // nombre_cancha, tipo_cancha, descripcion, precio_hora, estado, habilitado
        // Usaré los del MODELO.
        DB::table('canchas')->insert([
            [
                'cancha_id' => 1, // Si no es auto-incremental
                'nombre' => 'Cancha Central', // 'nombre'
                'tipo' => 'Parquet Profesional', // 'tipo'
                'precio_hora' => 30.00,
                'capacidad' => 10, // 'estado'      // 'habilitado'
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'cancha_id' => 2,
                'nombre' => 'Cancha Norte',
                'tipo' => 'arcilla',
                'precio_hora' => 30.00,
                'capacidad' => 10,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'cancha_id' => 3,
                'nombre' => 'Cancha Sur (Wally)',
                'tipo' => 'Parquet Adaptable Wally',
                'precio_hora' => 35.00, // Precio diferente para wally por ejemplo
                'capacidad' => 10,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
        ]);
        $this->command->info('Canchas estáticas creadas.');

        // DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}