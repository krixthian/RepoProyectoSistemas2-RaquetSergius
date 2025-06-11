<?php

namespace App\Services;

use App\Models\Reserva;
use App\Models\Cliente;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use App\Models\Cancha;

class ReservaService
{
    /**
     *
     * @param string $fechaString 
     * @return array|null 
     */
    public function getReservasConfirmadasPorFecha(string $fechaString): ?array
    {
        try {
            $fecha = Carbon::parse($fechaString)->format('Y-m-d');
            $reservas = Reserva::whereDate('fecha', $fecha)
                ->where('estado', 'Confirmada')
                ->get(['hora_inicio', 'hora_fin', 'estado', 'cancha_id'])
                ->toArray();
            return $reservas;
        } catch (\Exception $e) {
            Log::error("Error en ReservaService al obtener reservas para fecha {$fechaString}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica si un cliente ya tiene una reserva futura
     * @param int $clienteId 
     * @return bool 
     */
    public function clienteTieneReservaFutura(int $clienteId): bool
    {
        try {
            $hoy = Carbon::today()->toDateString();
            $tieneReserva = Reserva::where('cliente_id', $clienteId)
                ->where('fecha', '>=', $hoy)
                ->whereIn('estado', ['Pendiente', 'Confirmada'])
                ->exists();

            Log::info("ReservaService: Check future reservation for client {$clienteId}. Result: " . ($tieneReserva ? 'Yes' : 'No'));
            return $tieneReserva;

        } catch (\Exception $e) {
            Log::error("ReservaService: Error checking future reservation for client {$clienteId}: " . $e->getMessage());

            return true;
        }
    }

    /**
     * Crea una reserva para un cliente, asignando la primera cancha disponible.
     *
     * @param int $clienteId
     * @param string $fechaIso
     * @param string $horaInicioIso 
     * @param array|null $duracion 
     * @param string|null $horaFinIso 
     * @param Cliente $cliente 
     * @return array 
     */
    /**
     * Intenta crear una reserva en la primera cancha que encuentre libre.
     * La crea con estado 'Pendiente' y calcula el monto.
     * Devuelve el objeto Reserva en caso de éxito.
     */
    public function crearReservaEnPrimeraCanchaLibre(
        int $clienteId,
        string $fecha,
        string $horaInicio,
        ?array $duracionArray,
        ?string $horaFin,
        Cliente $cliente
    ): array { //
        $horaInicioCarbon = Carbon::parse($horaInicio);
        $horaFinCalculada = $horaFin ? Carbon::parse($horaFin) : null;

        if (!$horaFinCalculada && is_array($duracionArray)) {
            $amount = $duracionArray['amount'] ?? 0;
            $unit = $duracionArray['unit'] ?? 'hour';
            $horaFinCalculada = $horaInicioCarbon->copy();
            if ($unit === 'hour' || $unit === 'h') {
                $horaFinCalculada->addHours($amount);
            } elseif ($unit === 'min') {
                $horaFinCalculada->addMinutes($amount);
            }
        }

        if (!$horaFinCalculada) {
            return ['success' => false, 'message' => 'No se pudo determinar la hora de finalización de la reserva.'];
        }

        $canchasDisponibles = Cancha::where('disponible', true)->get();
        $canchaEncontrada = null;

        foreach ($canchasDisponibles as $cancha) {
            $conflicto = Reserva::where('cancha_id', $cancha->cancha_id)
                ->where('fecha', $fecha)
                ->whereIn('estado', ['Confirmada', 'Pendiente']) // Una reserva pendiente también ocupa el espacio
                ->where(function ($query) use ($horaInicio, $horaFinCalculada) {
                    $query->where(function ($q) use ($horaInicio, $horaFinCalculada) {
                        $q->where('hora_inicio', '<', $horaFinCalculada->format('H:i:s'))
                            ->where('hora_fin', '>', $horaInicio);
                    });
                })->exists();

            if (!$conflicto) {
                $canchaEncontrada = $cancha;
                break;
            }
        }

        if (!$canchaEncontrada) {
            return ['success' => false, 'message' => "Lo sentimos, no hay canchas disponibles en el horario de {$horaInicio} a " . $horaFinCalculada->format('H:i') . "."];
        }

        // --- Lógica de cálculo de precio (ajusta según tus reglas) ---
        $duracionEnHoras = $horaInicioCarbon->diffInMinutes($horaFinCalculada) / 60;
        $precioPorHora = $canchaEncontrada->precio_hora ?? 50; // Precio por defecto si no está definido
        $precioTotal = $duracionEnHoras * $precioPorHora;

        if ($canchaEncontrada) {
            try {
                DB::beginTransaction();
                $nuevaReserva = Reserva::create([
                    'cliente_id' => $clienteId,
                    'cancha_id' => $canchaEncontrada->cancha_id,
                    'fecha' => $fecha,
                    'hora_inicio' => $horaInicio,
                    'hora_fin' => $horaFinCalculada->format('H:i:s'),
                    'estado' => 'Pendiente', // Estado inicial
                    'monto_total' => $precioTotal,
                    'monto' => $precioTotal, // Monto inicial
                    'metodo_pago' => 'QR',
                    'pago_completo' => false, // Inicialmente no pagado

                ]);

                $cliente->last_activity_at = Carbon::now();
                $cliente->save();

                DB::commit();
                Log::info("Reserva PENDIENTE creada (ID: {$nuevaReserva->reserva_id}) para cliente {$clienteId}");
                return [
                    'success' => true,
                    'message' => "Tu solicitud de reserva ha sido registrada.",
                    'reserva' => $nuevaReserva // <-- Esencial devolver esto
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error de SQL al crear reserva PENDIENTE: " . $e->getMessage());
                return ['success' => false, 'message' => 'Hubo un error al registrar tu solicitud.'];
            }
        } else {
            return ['success' => false, 'message' => 'Lo sentimos, no hay canchas disponibles en ese horario.'];
        }
    }

    /**
     *
     * @param int $clienteId
     * @return Reserva|null    
     */
    //BUSCAR RESERVAR PROXIMA
    public function findUnicaReservaFutura(int $clienteId): ?Reserva
    {
        Log::info("ReservaService: Finding single future reservation for client {$clienteId}");
        try {
            $hoy = Carbon::today()->toDateString();
            $reservasFuturas = Reserva::where('cliente_id', $clienteId)
                ->where('fecha', '>=', $hoy)
                ->whereIn('estado', ['Pendiente', 'Confirmada'])
                ->orderBy('fecha', 'asc')
                ->get();


            if ($reservasFuturas->count() === 1) {
                Log::info("ReservaService: Found one future reservation.");
                return $reservasFuturas->first();
            } elseif ($reservasFuturas->count() > 1) {
                Log::warning("ReservaService: Found multiple ({$reservasFuturas->count()}) future reservations for client {$clienteId}. Cannot uniquely identify which one to cancel via bot.");

                return null;
            } else {
                Log::info("ReservaService: No future reservations found for client {$clienteId}.");
                return null;
            }

        } catch (\Exception $e) {
            Log::error("ReservaService: Error finding single future reservation for client {$clienteId}: " . $e->getMessage());
            return null;
        }
    }

    public function getReservasActivasFuturasPorCliente(int $clienteId)
    {
        $hoy = Carbon::today()->toDateString();
        $ahora = Carbon::now()->format('H:i:s');

        return Reserva::where('cliente_id', $clienteId)
            ->where('estado', 'Confirmada') // O el estado que uses para reservas válidas
            ->where(function ($query) use ($hoy, $ahora) {
                $query->where('fecha', '>', $hoy)
                    ->orWhere(function ($query) use ($hoy, $ahora) {
                        $query->where('fecha', '=', $hoy)
                            ->where('hora_fin', '>', $ahora);
                    });
            })
            ->with('cancha') // Para mostrar el nombre de la cancha
            ->orderBy('fecha', 'asc')
            ->orderBy('hora_inicio', 'asc')
            ->get();
    }

    /**
     *
     * @param Reserva $reserva
     * @param int $horasAntelacionMinimas
     * @return bool
     */
    public function cancelarReserva(Reserva $reserva, int $horasAntelacionMinimas = 2): bool
    {
        Log::info("ReservaService: Attempting to cancel reservation ID: {$reserva->reserva_id}");
        try {

            $fechaHoraInicioReserva = $reserva->fecha->copy()->setTimeFromTimeString($reserva->hora_inicio);
            $ahora = Carbon::now();

            // --- Validación  ---
            if ($ahora->gte($fechaHoraInicioReserva)) {
                Log::warning("ReservaService: Attempt to cancel reservation ID {$reserva->reserva_id} that has already started or passed.");
                return false;
            }
            if ($ahora->diffInHours($fechaHoraInicioReserva) < $horasAntelacionMinimas) {
                Log::warning("ReservaService: Attempt to cancel reservation ID {$reserva->reserva_id} within the minimum notice period ({$horasAntelacionMinimas} hours).");
                return false;
            }
            // --- Fin Validación ---


            // Verifica si ya está cancelada
            if ($reserva->estado === 'Cancelada') {
                Log::info("ReservaService: Reservation ID {$reserva->reserva_id} is already cancelled.");
                return true; // Ya estaba cancelada
            }

            // Actualiza el estado
            $reserva->estado = 'Cancelada';
            $success = $reserva->save();

            if ($success) {
                Log::info("ReservaService: Reservation ID {$reserva->reserva_id} cancelled successfully.");
                // TODO: disparar un evento enviar noti
            } else {
                Log::error("ReservaService: Failed to save cancellation for reservation ID {$reserva->reserva_id}.");
            }
            return $success;

        } catch (\Exception $e) {
            Log::error("ReservaService: Error cancelling reservation ID {$reserva->reserva_id}: " . $e->getMessage());
            return false;
        }
    }

    public function asociarComprobanteAReserva(int $reservaId, string $rutaComprobante): bool
    {
        $reserva = Reserva::find($reservaId);
        if ($reserva && $reserva->estado === 'Pendiente') {
            $reserva->ruta_comprobante_pago = $rutaComprobante;
            $reserva->save();
            Log::info("Comprobante '{$rutaComprobante}' asociado a reserva ID {$reservaId}.");
            return true;
        }
        Log::warning("No se pudo asociar comprobante. Reserva ID {$reservaId} no encontrada o no está en estado 'Pendiente'.");
        return false;
    }
    public function findReservaById(int $reservaId): ?Reserva
    {
        try {
            $reserva = Reserva::where('reserva_id', $reservaId)->first();
            if (!$reserva) {
                Log::warning("ReservaService: No reservation found with ID {$reservaId}");
                return null;
            }
            return $reserva;
        } catch (\Exception $e) {
            Log::error("ReservaService: Error finding reservation by ID {$reservaId}: " . $e->getMessage());
            return null;
        }
    }
}