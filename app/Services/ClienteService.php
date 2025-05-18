<?php

namespace App\Services;

use App\Models\Cliente;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ClienteService
{
    public function findClienteByTelefono(string $telefono): ?Cliente
    {
        try {
            $cliente = Cliente::where('telefono', $telefono)->first();

            if ($cliente) {
                Log::info("ClienteService: Found client with ID {$cliente->cliente_id} for phone {$telefono}");
            } else {
                Log::info("ClienteService: Client not found for phone {$telefono}");
            }
            return $cliente;

        } catch (\Exception $e) {
            Log::error("ClienteService: Error finding client by phone {$telefono}: " . $e->getMessage());
            return null;
        }
    }

    /**
     *
     * @param string $telefono
     * @param array $datosAdicionales
     * @return Cliente
     * @throws \Exception 
     */
    public function findOrCreateByTelefono(string $telefono, array $datosAdicionales = []): Cliente
    {
        $cliente = Cliente::where('telefono', $telefono)->first();

        if ($cliente) {
            Log::info("Cliente encontrado por teléfono {$telefono}: ID {$cliente->id}");
            return $cliente;
        }

        Log::info("Cliente no encontrado por teléfono {$telefono}. Creando nuevo cliente.");




        try {
            $cliente = Cliente::create([
                'telefono' => $telefono,
                'nombre' => $datosAdicionales['nombre'] ?? null,
            ]);
            Log::info("Cliente creado con ID {$cliente->id} para teléfono {$telefono}");
            return $cliente;
        } catch (\Exception $e) {
            Log::error("Error al crear cliente para teléfono {$telefono}: " . $e->getMessage());
            throw new \Exception("No se pudo crear el cliente.");
        }
    }

}