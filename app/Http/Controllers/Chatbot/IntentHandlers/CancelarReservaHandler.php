<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ReservaService;
use App\Services\ClienteService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CancelarReservaHandler implements IntentHandlerInterface
{
    protected $reservaService;
    protected $clienteService;
    private const MIN_HORAS_CANCELACION = 2;

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
        Log::info('Executing CancelarReservaHandler for senderId: ' . $senderId);
        Carbon::setLocale('es'); // Para formatos de fecha en español

        //buscar cliente
        $cliente = $this->clienteService->findClienteByTelefono($senderId);
        if (!$cliente) {
            Log::warning("CancelarReservaHandler: Client not found for senderId: " . $senderId);
            return "No pude encontrarte en nuestro sistema. Si tienes una reserva, por favor contacta a recepción para cancelarla.";
        }
        $clienteId = $cliente->cliente_id;
        Log::info("CancelarReservaHandler: Found client {$clienteId} ({$cliente->nombre})");

        //Buscar la reserva única
        $reservaParaCancelar = $this->reservaService->findUnicaReservaFutura($clienteId);

        //Validar si se encontro una reserva cancelable
        if ($reservaParaCancelar === null) {
            Log::info("CancelarReservaHandler: No single future reservation found for client {$clienteId} to cancel.");
            if ($this->reservaService->clienteTieneReservaFutura($clienteId)) {
                // Si esto es true, significa que findUnicaReservaFutura devolvió null porque había MÁS de una
                return "Hola {$cliente->nombre}, parece que tienes más de una reserva activa. Para cancelar, por favor contacta directamente a recepción indicando cuál deseas anular.";
            } else {
                // No tenía ninguna reserva futura
                return "Hola {$cliente->nombre}, no encontré reservas futuras activas a tu nombre para cancelar.";
            }
        }

        // --- Si se llega aqui se econtrof UNA reserva ---
        $fechaReserva = Carbon::parse($reservaParaCancelar->fecha)->format('d/m/Y');
        $horaInicioReserva = Carbon::parse($reservaParaCancelar->hora_inicio)->format('H:i');
        Log::info("CancelarReservaHandler: Found reservation ID {$reservaParaCancelar->reserva_id} for client {$clienteId} on {$fechaReserva} at {$horaInicioReserva}. Attempting cancellation.");

        // - Intentar cancelar la reserva usando el servicio
        $cancelacionExitosa = $this->reservaService->cancelarReserva($reservaParaCancelar, self::MIN_HORAS_CANCELACION);

        // - Informar al usuario
        if ($cancelacionExitosa) {

            if ($reservaParaCancelar->estado === 'Cancelada') {
                Log::info("CancelarReservaHandler: Reservation ID {$reservaParaCancelar->reserva_id} was successfully cancelled.");
                return "¡Listo, {$cliente->nombre}! Tu reserva para el {$fechaReserva} a las {$horaInicioReserva} ha sido cancelada exitosamente.";
            } else {

                Log::error("CancelarReservaHandler: Cancellation reported success by service, but state didn't change for reservation ID {$reservaParaCancelar->reserva_id}.");
                return "Hubo un problema al intentar actualizar el estado de tu reserva. Por favor, contacta a recepción para confirmar la cancelación.";
            }
        } else {

            $fechaHoraInicioReserva = Carbon::parse($reservaParaCancelar->fecha . ' ' . $reservaParaCancelar->hora_inicio);
            $horasRestantes = Carbon::now()->diffInHours($fechaHoraInicioReserva, false); // false para obtener negativo si ya pasó

            Log::warning("CancelarReservaHandler: Cancellation failed for reservation ID {$reservaParaCancelar->reserva_id}. Hours remaining: {$horasRestantes}");

            if ($horasRestantes >= 0 && $horasRestantes < self::MIN_HORAS_CANCELACION) {
                // Está dentro del plazo
                return "Lo siento, {$cliente->nombre}, tu reserva para el {$fechaReserva} a las {$horaInicioReserva} ya no puede ser cancelada automáticamente porque falta muy poco tiempo (menos de " . self::MIN_HORAS_CANCELACION . " horas). Por favor, contacta a recepción si es urgente.";
            } else if ($horasRestantes < 0) {
                // La reserva ya se paso
                return "Hola {$cliente->nombre}, la reserva del {$fechaReserva} a las {$horaInicioReserva} ya ha pasado y no se puede cancelar.";
            } else {
                // Otro error
                return "Lo siento, {$cliente->nombre}, no se pudo cancelar tu reserva del {$fechaReserva} a las {$horaInicioReserva} en este momento debido a un error inesperado. Por favor, contacta a recepción.";
            }
        }
    }
}