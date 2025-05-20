<?php // database/seeders/DatabaseSeeder.php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Iniciando el proceso de Seeding...');

        $this->call([
            StaticDataSeeder::class,
            ClienteSeeder::class,
        ]);
        $this->call(ActividadClienteSeeder::class);
        $this->command->info('Seeders de datos básicos y actividades completados.');

        $this->command->info('Ejecutando UpdateChurnStatusCommand para actualizar estado de churn...');
        Artisan::call('app:update-churn-status'); //EJECUTAR EL COMANDO
        $this->command->info('UpdateChurnStatusCommand ejecutado.');

        $this->command->info('¡Todos los seeders y comandos post-seed se ejecutaron exitosamente!');
    }
}