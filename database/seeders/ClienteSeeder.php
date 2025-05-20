<?php // database/seeders/ClienteSeeder.php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Cliente;

class ClienteSeeder extends Seeder
{
    public function run()
    {
        Cliente::factory()->count(200)->create();
        $this->command->info('Clientes de prueba creados.');
    }
}