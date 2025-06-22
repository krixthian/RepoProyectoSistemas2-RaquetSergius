<?php

namespace App\Http\Controllers\Chatbot\IntentHandlers;

use App\Chatbot\IntentHandlerInterface;
use App\Services\ClienteService;
use App\Models\Premio; // Asegúrate que tu modelo se llame así
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MostrarPremiosHandler implements IntentHandlerInterface
{
    protected ClienteService $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    public function handle(array $parameters, string $senderId, ?string $action = null): array
    {
        Log::info("[MostrarPremiosHandler] Executing for senderId: {$senderId}");
        Carbon::setLocale('es');

        // 1. Obtener los datos del cliente, incluyendo sus puntos.
        $cliente = $this->clienteService->findClienteByTelefono($senderId);
        $puntosCliente = $cliente->puntos ?? 0;

        $mensaje = "Hola " . ($cliente->nombre ?? 'estimado cliente') . "! Tienes *{$puntosCliente} puntos* disponibles.\n\n";
        $mensaje .= "Estos son los premios que puedes canjear:\n";

        // 2. Consultar los premios disponibles.
        $hoy = Carbon::today();
        $premiosDisponibles = Premio::where('activo', true)
            ->where(function ($query) use ($hoy) {
                // El premio es válido si no tiene fecha de inicio o la fecha de inicio ya pasó
                $query->whereNull('valido_desde')->orWhere('valido_desde', '<=', $hoy);
            })
            ->where(function ($query) use ($hoy) {
                // El premio es válido si no tiene fecha de fin o la fecha de fin aún no ha llegado
                $query->whereNull('valido_hasta')->orWhere('valido_hasta', '>=', $hoy);
            })
            ->where(function ($query) {
                // El premio está disponible si no tiene stock definido (infinito) o si el stock es mayor a 0
                $query->whereNull('stock')->orWhere('stock', '>', 0);
            })
            ->with('claseZumbaGratis') // Cargar la relación para obtener el nombre de la clase
            ->orderBy('puntos_requeridos', 'asc')
            ->get();

        // 3. Construir el mensaje de respuesta.
        if ($premiosDisponibles->isEmpty()) {
            $mensaje .= "\nActualmente no hay premios disponibles. ¡Sigue acumulando puntos!";
        } else {
            $mensaje .= "\nLos premios para los que tienes puntos suficientes aparecen con '✅' ";
            foreach ($premiosDisponibles as $premio) {
                // Ícono para indicar si el cliente puede canjearlo
                $icono = ($puntosCliente >= $premio->puntos_requeridos) ? '✅' : '❌';

                $mensaje .= "\n{$icono} *{$premio->nombre}* \n";
                $mensaje .= "   - Puntos requeridos: *{$premio->puntos_requeridos}*\n";

                // Añadir detalles según el tipo de premio
                switch ($premio->tipo) {
                    case 'Descuento Valor':
                        if ($premio->valor_descuento) {
                            $mensaje .= "   - Descripción: Un descuento de *Bs. " . number_format($premio->valor_descuento, 2) . "* en tu próxima compra/reserva.\n";
                        }
                        break;
                    case 'Descuento Porcentaje':
                        if ($premio->porcentaje_descuento) {
                            $mensaje .= "   - Descripción: Un *{$premio->porcentaje_descuento}% de descuento* en tu próxima compra/reserva.\n";
                        }
                        break;
                    case 'Clase Gratis':
                        $nombreClase = $premio->claseZumbaGratis->nombre_clase ?? 'una clase seleccionada'; // Asumiendo que ClaseZumba tiene 'nombre_clase'
                        $mensaje .= "   - Descripción: Una inscripción gratuita a *{$nombreClase}*.\n";
                        break;
                    case 'Producto':
                        if ($premio->producto_nombre) {
                            $mensaje .= "   - Descripción: Llévate un(a) *{$premio->producto_nombre}* de regalo.\n";
                        }
                        break;
                    default:
                        if ($premio->descripcion) {
                            $mensaje .= "   - Descripción: {$premio->descripcion}\n";
                        }
                        break;
                }
                if ($premio->stock !== null) {
                    $mensaje .= "   - Disponibles: ¡Solo quedan {$premio->stock}!\n";
                }
            }
            $mensaje .= "\nPara canjear un premio, puedes hacerlo acercandote a administración y pedirle el premio que deseas canjear si tus puntos alcanzan.";
        }

        return [
            'messages_to_send' => [
                [
                    'fulfillmentText' => $mensaje,
                    'message_type' => 'text',
                    'payload' => []
                ]
            ],
            'outputContextsToSetActive' => []
        ];
    }
}