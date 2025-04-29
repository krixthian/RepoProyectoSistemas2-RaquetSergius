<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ReservaService;
use App\Services\ClienteService; // Necesitamos buscar al cliente
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ConsultaReservaHandler implements IntentHandlerInterface
{
    protected $reservaService;
    protected $clienteService;


    public function __construct(ReservaService $reservaService, ClienteService $clienteService)
    {
        $this->reservaService = $reservaService;
        $this->clienteService = $clienteService;
    }

    /**
     * Maneja la solicitud de cancelación de reserva.
     * Asume que el usuario quiere cancelar su *única* reserva futura activa.
     *
     * @param array $parameters Parámetros de Dialogflow (puede estar vacío).
     * @param string $senderId ID del remitente (número de WhatsApp).
     * @return string Respuesta para el usuario.
     */
    public function handle(array $parameters, string $senderId): string
    {
        Log::info('Executing ConsultarReservaHandler for senderId: ' . $senderId);
        Carbon::setLocale('es');

        // 1. Buscar al cliente
        $cliente = $this->clienteService->findClienteByTelefono($senderId);
        if (!$cliente) {
            Log::warning("ConsultarReservaHandler: Client not found for senderId: " . $senderId);
            return "No pude encontrarte en nuestro sistema. Si tienes una reserva, por favor contacta a recepción para saberlo";
        }
        $clienteId = $cliente->cliente_id;
        Log::info("ConsultarReservaHandler: Found client {$clienteId} ({$cliente->nombre})");

        // 2. Buscar la reserva única futura del cliente
        $reservaConsulta = $this->reservaService->findUnicaReservaFutura($clienteId);

        // 3. Validar si se encontró una reserva cancelable
        if ($reservaConsulta === null) {
            // Razones: No hay reservas futuras, hay más de una, o hubo error en la búsqueda.
            // El log del servicio debería indicar la razón exacta.
            Log::info("ConsultarReservaHandler: No single future reservation found for client {$clienteId} to cancel.");
            // Verificar si tiene *alguna* reserva futura para dar un mensaje más específico
            if ($this->reservaService->clienteTieneReservaFutura($clienteId)) {
                // Si esto es true, significa que findUnicaReservaFutura devolvió null porque había MÁS de una
                return "Hola {$cliente->nombre}, parece que tienes más de una reserva activa. por favor contacta directamente a recepción indicando cuál deseas consultar.";
            } else {
                // No tenía ninguna reserva futura
                return "Hola {$cliente->nombre}, no encontré reservas futuras activas a tu nombre, si estas seguro que hiciste una puedes comunicarte con recepcion o intentar hacerla nuevamente.";
            }
        }

        // --- si llega aqui, se encontró UNA reserva ---
        $fechaReserva = Carbon::parse($reservaConsulta->fecha)->format('d/m/Y');
        $horaInicioReserva = Carbon::parse($reservaConsulta->hora_inicio)->format('H:i');
        $horaFinReserva = Carbon::parse($reservaConsulta->hora_fin)->format('H:i');
        Log::info("ConsultarReservaHandler: Found reservation ID {$reservaConsulta->reserva_id} for client {$clienteId} on {$fechaReserva} at {$horaInicioReserva}. Attempting cancellation.");

        return "Hola {$cliente->nombre}, tu reserva está programada para el {$fechaReserva} desde las {$horaInicioReserva} hasta las {$horaFinReserva}. Si necesitas más información, por favor contacta a recepción.";
    }
}