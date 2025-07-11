<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Detalle Reserva #{{ $reserva->reserva_id }}</title>
    <style>
        /* 1. Paleta de Colores y Estilos Base */
        :root {
            --background-color: #1a1a1a;
            --surface-color: #242424;
            --primary-color: #00aaff;
            --text-color: #e0e0e0;
            --text-muted-color: #888;
            --border-color: #333;
            --danger-color: #ff3b30;
            --success-color: #34c759;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 2rem;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        h1,
        h2 {
            color: var(--primary-color);
            font-weight: 600;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
        }

        h1 {
            margin-bottom: 2rem;
        }

        h2 {
            margin-top: 3rem;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }

        /* 2. Tarjetas y Detalles */
        .detail-card {
            background-color: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .detail-item strong {
            display: block;
            color: var(--text-muted-color);
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .detail-item span {
            font-size: 1.1rem;
        }

        /* 3. Comprobante de Pago */
        .comprobante-container img {
            max-width: 100%;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .comprobante-container p {
            color: var(--text-muted-color);
            text-align: center;
        }

        /* 4. Botones y Acciones */
        .actions-form {
            margin-top: 2rem;
            background-color: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-muted-color);
        }

        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            box-sizing: border-box;
            border: 1px solid var(--border-color);
            background-color: var(--background-color);
            color: var(--text-color);
            border-radius: 8px;
            font-size: 1rem;
            resize: vertical;
            /* Permite redimensionar verticalmente */
        }

        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 170, 255, 0.3);
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
        }

        /* Para mostrar errores de validación */
        .alert-danger {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--danger-color);
            border-radius: 4px;
            background-color: rgba(255, 59, 48, 0.1);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
        }

        .button,
        .button-link {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .button-link:hover,
        .button:hover {
            transform: translateY(-2px);
        }

        .button-success {
            background-color: var(--success-color);
        }

        .button-success:hover {
            background-color: #2da34b;
        }

        .button-danger {
            background-color: var(--danger-color);
        }

        .button-danger:hover {
            background-color: #c7001e;
        }

        .button-secondary {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .button-secondary:hover {
            background-color: rgba(0, 170, 255, 0.1);
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Detalle de la Reserva</h1>

        <div class="detail-card">
            <div class="detail-grid">
                <div class="detail-item"><strong>ID Reserva</strong><span>#{{ $reserva->reserva_id }}</span></div>
                <div class="detail-item"><strong>Cliente</strong><span>{{ $reserva->cliente->nombre ?? '–' }}
                        {{ $reserva->cliente->apellido ?? '' }}</span></div>
                <div class="detail-item"><strong>Cancha</strong><span>{{ $reserva->cancha->nombre ?? '–' }}</span></div>
                <div class="detail-item">
                    <strong>Fecha</strong><span>{{ \Carbon\Carbon::parse($reserva->fecha)->format('d/m/Y') }}</span>
                </div>
                <div class="detail-item">
                    <strong>Horario</strong><span>{{ \Carbon\Carbon::parse($reserva->hora_inicio)->format('H:i') }} a
                        {{ \Carbon\Carbon::parse($reserva->hora_fin)->format('H:i') }}</span>
                </div>
                <div class="detail-item"><strong>Monto Total</strong><span>{{ number_format($reserva->monto, 2) }}
                        Bs.</span></div>
                <div class="detail-item"><strong>Estado</strong><span>{{ $reserva->estado }}</span></div>
                <div class="detail-item"><strong>Método
                        Pago</strong><span>{{ $reserva->metodo_pago ?? 'No especificado' }}</span></div>
            </div>
        </div>

        {{-- NUEVA SECCIÓN PARA MOSTRAR EL COMPROBANTE --}}
        <h2>Comprobante de Pago</h2>
        <div class="detail-card comprobante-container">
            @if ($reserva->ruta_comprobante_pago)
                <img src="{{ asset('storage/' . $reserva->ruta_comprobante_pago) }}" alt="Comprobante de pago">
            @else
                <p>No se ha subido un comprobante de pago para esta reserva.</p>
            @endif
        </div>

        {{-- FORMULARIO DE ACCIONES (CONFIRMAR/RECHAZAR) --}}
        @if ($reserva->estado == 'Pendiente')
            <form id="action-form" method="POST" class="actions-form">
                @csrf
                <h2>Acciones de Revisión</h2>

                <div class="form-group">
                    <label for="mensaje">Mensaje adicional para el cliente (obligatorio)</label>
                    <textarea name="mensaje" id="mensaje" rows="4"
                        placeholder="Ej:fue rechazado por que el comprobante no es valido" required minlength="10">{{ old('mensaje') }}</textarea>
                </div>

                <div class="form-actions">
                    <a href="{{ route('admin.reservas.pendientes') }}" class="button-link button-secondary">Cancelar
                        y Volver</a>

                    <div class="action-buttons">
                        <button type="submit" class="button button-danger" data-action="rechazar"
                            data-confirm="¿Estás seguro de que deseas RECHAZAR esta reserva?">
                            Rechazar Reserva
                        </button>
                        <button type="submit" class="button button-success" data-action="confirmar"
                            data-confirm="¿Estás seguro de que deseas CONFIRMAR esta reserva?">
                            Confirmar Reserva
                        </button>
                    </div>
                </div>
            </form>
        @else
            <div class="form-actions">
                <a href="{{ route('admin.reservas.pendientes') }}" class="button-link button-secondary">Volver al
                    listado</a>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('action-form');
            if (form) {
                // URLs para las acciones, obtenidas desde las rutas de Laravel
                const urlConfirmar = "{{ route('reservas.confirmar', $reserva->reserva_id) }}";
                const urlRechazar = "{{ route('reservas.rechazar', $reserva->reserva_id) }}";

                form.addEventListener('submit', function(event) {
                    // Previene el envío inmediato del formulario
                    event.preventDefault();

                    // Obtiene el botón que fue presionado
                    const submitter = event.submitter;
                    const actionType = submitter.dataset.action;
                    const confirmationMessage = submitter.dataset.confirm;

                    // Valida que el textarea no esté vacío (aunque ya tiene 'required')
                    const mensajeTextarea = document.getElementById('mensaje');
                    if (mensajeTextarea.value.trim().length < 10) {
                        alert('Por favor, escribe un mensaje de al menos 10 caracteres para el cliente.');
                        mensajeTextarea.focus();
                        return;
                    }

                    // Muestra el diálogo de confirmación
                    if (confirm(confirmationMessage)) {
                        // Asigna la URL correcta a la acción del formulario
                        if (actionType === 'confirmar') {
                            form.action = urlConfirmar;
                        } else if (actionType === 'rechazar') {
                            form.action = urlRechazar;
                        }

                        // Envía el formulario
                        form.submit();
                    }
                });
            }
        });
    </script>
</body>

</html>
