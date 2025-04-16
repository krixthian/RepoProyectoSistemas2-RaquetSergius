<?php

namespace App\Services;

use App\Models\Cliente; // Importa el modelo Cliente
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ClienteService
{
    /**
     * Busca un cliente por su número de teléfono.
     * Por ahora, no crea un cliente si no existe.
     *
     * @param string $telefono Número de teléfono (senderId de WhatsApp)
     * @return Cliente|null Retorna el modelo Cliente si se encuentra, o null si no.
     */
    public function findClienteByTelefono(string $telefono): ?Cliente
    {
        try {
            // Asume que la columna en la BD se llama 'telefono'
            $cliente = Cliente::where('telefono', $telefono)->first();

            if ($cliente) {
                Log::info("ClienteService: Found client with ID {$cliente->cliente_id} for phone {$telefono}");
            } else {
                Log::info("ClienteService: Client not found for phone {$telefono}");
            }
            return $cliente;

        } catch (\Exception $e) {
            Log::error("ClienteService: Error finding client by phone {$telefono}: " . $e->getMessage());
            return null; // Devuelve null en caso de error
        }
    }

    // --- Opcional: Método para crear cliente ---

    public function findOrCreateByTelefono(string $telefono, string $nombreDefault = null): ?Cliente
    {
        Log::info("ClienteService: Finding or creating client for phone {$telefono}");

        // Atributos para BUSCAR el cliente existente.
        // ¡Asegúrate que 'telefono' sea la columna correcta en tu tabla clientes!
        $attributesToFind = ['telefono' => $telefono];

        // Atributos ADICIONALES para usar SÓLO si se CREA un nuevo cliente.
        // Deben estar en la propiedad $fillable del modelo Cliente.
        $attributesToCreate = [
            // Asigna un nombre genérico si no se proporciona uno
            'nombre' => $nombreDefault ?? ('Cliente WhatsApp ' . substr($telefono, -4)),
            // Establece la fecha de registro como hoy
            'fecha_registro' => Carbon::today()->toDateString(),
            // Establece valores por defecto para otros campos fillable si es necesario
            'cliente_frecuente' => false,
            'email' => null, // Asumiendo que el email puede ser nulo
        ];

        try {
            // Intenta encontrar por teléfono, si no, crea con los atributos combinados
            $cliente = Cliente::firstOrCreate($attributesToFind, $attributesToCreate);

            // Puedes saber si se acaba de crear consultando la propiedad wasRecentlyCreated
            if ($cliente->wasRecentlyCreated) {
                Log::info("ClienteService: Created new client with ID {$cliente->cliente_id} for phone {$telefono}");
            } else {
                Log::info("ClienteService: Found existing client with ID {$cliente->cliente_id} for phone {$telefono}");
            }

            return $cliente;

        } catch (\Exception $e) {
            // Captura cualquier error de base de datos durante la búsqueda o creación
            Log::error("ClienteService: Error finding or creating client for phone {$telefono}: " . $e->getMessage());
            return null; // Devuelve null para indicar que hubo un problema
        }
    }

}