<?php

namespace App\Services;

use App\Models\Cliente;
// Se elimina: use App\Models\User;
use Illuminate\Support\Facades\Log;
// Se elimina: use Illuminate\Support\Facades\Hash;
// Se elimina: use Illuminate\Support\Str;

class ClienteService
{
    /**
     * Encuentra un cliente por su número de teléfono. Si no existe, lo crea.
     * No crea un registro User asociado.
     *
     * @param string $telefono El número de teléfono del cliente.
     * @param array $datosAdicionales Datos para crear el cliente (ej. nombre, email desde el perfil de WhatsApp).
     * Espera claves como 'nombre_perfil_whatsapp', 'email'.
     * @return array ['cliente' => Cliente, 'is_new_requiring_data' => bool]
     * @throws \Exception Si ocurre un error durante la creación del cliente.
     */
    public function findOrCreateByTelefono(string $telefono, array $datosAdicionales = []): array
    {
        $cliente = Cliente::where('telefono', $telefono)->first();
        $isNewRequiringData = false;

        if ($cliente) {
            Log::info("Cliente encontrado por teléfono {$telefono}: ID {$cliente->id}");

            // Si el cliente existe pero no tiene nombre, y no estamos en un flujo que omita la pregunta
            if (empty($cliente->nombre) && empty($datosAdicionales['nombre_skip_prompt'])) {
                $isNewRequiringData = true;
            }

            // Opcional: Actualizar datos si se proporcionan y son diferentes
            // Asegúrate de que las claves en datosAdicionales coincidan con lo que esperas
            $updateData = [];
            if (isset($datosAdicionales['nombre']) && $cliente->nombre !== $datosAdicionales['nombre']) {
                $updateData['nombre'] = $datosAdicionales['nombre'];
            }
            // Si también pasas 'apellidos' en datosAdicionales
            if (isset($datosAdicionales['apellidos']) && $cliente->apellidos !== $datosAdicionales['apellidos']) {
                $updateData['apellidos'] = $datosAdicionales['apellidos'];
            }
            if (array_key_exists('email', $datosAdicionales) && $cliente->email !== $datosAdicionales['email']) {
                $updateData['email'] = $datosAdicionales['email'] ? strtolower(trim($datosAdicionales['email'])) : null;
            }

            if (!empty($updateData)) {
                $cliente->update($updateData);
                Log::info("Cliente ID {$cliente->id} actualizado con datos adicionales.", $updateData);
                // Si se actualizó el nombre que faltaba, ya no requiere datos.
                if (isset($updateData['nombre']) && !empty($updateData['nombre'])) {
                    $isNewRequiringData = false;
                }
            }
            return ['cliente' => $cliente, 'is_new_requiring_data' => $isNewRequiringData];
        }

        Log::info("Cliente no encontrado por teléfono {$telefono}. Creando nuevo cliente sin User asociado.");

        // Preparar datos para el nuevo cliente
        $clienteDataToCreate = [
            'telefono' => $telefono,
            'nombre' => $datosAdicionales['nombre_perfil_whatsapp'] ?? "Cliente-{$telefono}",
            'apellidos' => $datosAdicionales['apellidos'] ?? null,
            // Usar el email de datosAdicionales si está, si no, nulo. Validar formato si es necesario.
            'email' => isset($datosAdicionales['email']) ? strtolower(trim($datosAdicionales['email'])) : null,
            'user_id' => null, // Explícitamente nulo ya que no creamos User
            'estado' => $datosAdicionales['estado'] ?? 'Activo', // Estado por defecto para nuevos clientes
            'fecha_registro' => now(), // Fecha de creación
        ];

        // Limpiar email si es una cadena vacía para asegurar que se guarde como NULL si la BD lo requiere
        if ($clienteDataToCreate['email'] === '') {
            $clienteDataToCreate['email'] = null;
        }


        try {
            $cliente = Cliente::create($clienteDataToCreate);

            // Si el nombre no se pudo obtener y no se proporcionó, entonces requiere datos.
            if (empty($cliente->nombre) && empty($datosAdicionales['nombre_skip_prompt'])) {
                $isNewRequiringData = true;
            }

            Log::info("Cliente creado con ID {$cliente->id} para teléfono {$telefono}. Requiere datos: " . ($isNewRequiringData ? 'Sí' : 'No'), $cliente->toArray());
            return ['cliente' => $cliente, 'is_new_requiring_data' => $isNewRequiringData];

        } catch (\Exception $e) {
            Log::error("Error al crear cliente para teléfono {$telefono}: " . $e->getMessage(), ['data' => $clienteDataToCreate]);
            throw new \Exception("No se pudo crear el cliente debido a un error interno.");
        }
    }

    /**
     * Actualiza los datos de un cliente.
     * @param string $telefono
     * @param array $datosNuevos ['nombre' => 'Nuevo Nombre', 'email' => 'nuevo@email.com', 'apellidos' => '...']
     * @return Cliente|null El cliente actualizado o null si no se encontró o hubo error.
     */
    public function actualizarDatosCliente(string $telefono, array $datosNuevos): ?Cliente
    {
        $cliente = $this->findClienteByTelefono($telefono);
        if ($cliente) {
            $updatePayload = [];
            if (isset($datosNuevos['nombre'])) {
                $updatePayload['nombre'] = trim($datosNuevos['nombre']);
            }
            if (isset($datosNuevos['apellidos'])) {
                $updatePayload['apellidos'] = trim($datosNuevos['apellidos']);
            }

            // Permitir establecer email a null o a un valor
            if (array_key_exists('email', $datosNuevos)) {
                $emailInput = $datosNuevos['email'] !== null ? strtolower(trim($datosNuevos['email'])) : null;
                if ($emailInput === '' || $emailInput === 'no') { // Considerar "no" como intención de anular el email
                    $updatePayload['email'] = null;
                } elseif ($emailInput && !filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
                    Log::warning("Intento de actualizar con email inválido: {$emailInput} para cliente {$telefono}");
                    // Podrías retornar un error específico o lanzar una excepción
                    // throw new \InvalidArgumentException("El formato del correo electrónico no es válido.");
                    return null; // Indica error al que llama
                } else {
                    $updatePayload['email'] = $emailInput;
                }
            }

            if (!empty($updatePayload)) {
                try {
                    $cliente->update($updatePayload);
                    Log::info("Datos actualizados para cliente {$telefono}", $updatePayload);
                    return $cliente;
                } catch (\Exception $e) {
                    Log::error("Error al actualizar datos del cliente {$telefono}: " . $e->getMessage(), $updatePayload);
                    return null; // Indica error
                }
            }
            return $cliente; // Retorna el cliente aunque no haya habido cambios que guardar
        }
        Log::warning("Intento de actualizar datos para cliente no encontrado: {$telefono}");
        return null;
    }

    /**
     * Encuentra un cliente por su número de teléfono.
     */
    public function findClienteByTelefono(string $telefono): ?Cliente
    {
        return Cliente::where('telefono', $telefono)->first();
    }
}