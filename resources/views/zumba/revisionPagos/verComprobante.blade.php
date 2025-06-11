@php
    // Obtenemos los datos comunes de la primera inscripción del grupo
    $primera_inscripcion = $inscripciones->first();
    $cliente = $primera_inscripcion->cliente;
    $ruta_comprobante = $primera_inscripcion->ruta_comprobante_pago;
    $comprobanteHash = str_replace('/', '-', $ruta_comprobante);
@endphp

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Revisar Pago de Inscripción - Cliente: {{ $cliente->nombre }}</title>
    {{-- Copia los estilos de tu vista verReserva.blade.php aquí --}}
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
        <h1>Revisión de Pago de Inscripción a Zumba</h1>

        <div class="detail-card">
            <h2>Datos del Cliente</h2>
            <div class="detail-grid">
                <div class="detail-item"><strong>Cliente</strong><span>{{ $cliente->nombre ?? '–' }}</span></div>
                <div class="detail-item"><strong>Teléfono</strong><span>{{ $cliente->telefono ?? '–' }}</span></div>
            </div>
        </div>

        <h2>Comprobante de Pago</h2>
        <div class="detail-card comprobante-container">
            @if ($ruta_comprobante)
                <img src="{{ asset('storage/' . $ruta_comprobante) }}" alt="Comprobante de pago">
            @else
                <p>No se ha subido un comprobante de pago.</p>
            @endif
        </div>

        <h2>Clases Incluidas en este Pago</h2>
        <div class="detail-card">
            <ul>
                @php $montoTotal = 0; @endphp
                @foreach ($inscripciones as $inscripcion)
                    @if ($inscripcion->claseZumba)
                        <li>
                            <strong>Clase ID {{ $inscripcion->clase_id }}:</strong>
                            {{ $inscripcion->claseZumba->diasemama }}
                            ({{ \Carbon\Carbon::parse($inscripcion->fecha_clase)->format('d/m/Y') }})
                            a las {{ \Carbon\Carbon::parse($inscripcion->claseZumba->hora_inicio)->format('H:i') }}
                            - <strong>Bs. {{ number_format($inscripcion->monto_pagado, 2) }}</strong>
                        </li>
                        @php $montoTotal += $inscripcion->monto_pagado; @endphp
                    @endif
                @endforeach
            </ul>
            <hr style="border-color: var(--border-color); margin: 1rem 0;">
            <p style="text-align: right; font-size: 1.2rem; font-weight: bold;">
                Monto Total del Comprobante: Bs. {{ number_format($montoTotal, 2) }}
            </p>
        </div>

        <form id="action-form" method="POST" class="actions-form">
            @csrf
            <h2>Acciones de Revisión</h2>
            <div class="form-group">
                <label for="mensaje">Mensaje adicional para el cliente (obligatorio)</label>
                <textarea name="mensaje" id="mensaje" rows="4" placeholder="Ej: Pago recibido correctamente. ¡Te esperamos!"
                    required minlength="10">{{ old('mensaje') }}</textarea>
            </div>
            <div class="form-actions">
                <a href="{{ route('zumba.pendientes') }}" class="button-link button-secondary">Cancelar y
                    Volver</a>
                <div class="action-buttons">
                    <button type="submit" class="button button-danger" data-action="rechazar">Rechazar
                        Inscripciones</button>
                    <button type="submit" class="button button-success" data-action="confirmar">Confirmar
                        Inscripciones</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('action-form');
            if (form) {
                const urlConfirmar =
                    "{{ route('zumba.pendientes.confirmar', ['cliente_id' => $cliente->cliente_id, 'comprobante_hash' => $comprobanteHash]) }}";
                const urlRechazar =
                    "{{ route('zumba.pendientes.rechazar', ['cliente_id' => $cliente->cliente_id, 'comprobante_hash' => $comprobanteHash]) }}";

                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    const submitter = event.submitter;
                    const actionType = submitter.dataset.action;

                    if (actionType === 'confirmar') {
                        if (confirm('¿Estás seguro de que deseas CONFIRMAR este grupo de inscripciones?')) {
                            form.action = urlConfirmar;
                            form.submit();
                        }
                    } else if (actionType === 'rechazar') {
                        if (confirm('¿Estás seguro de que deseas RECHAZAR este grupo de inscripciones?')) {
                            form.action = urlRechazar;
                            form.submit();
                        }
                    }
                });

                // Asignar el data-action a los botones para que el event.submitter funcione
                form.querySelector('.button-danger').dataset.action = 'rechazar';
                form.querySelector('.button-success').dataset.action = 'confirmar';
            }
        });
    </script>
</body>

</html>
