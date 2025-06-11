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
        max-width: 800px; /* Ancho más adecuado para formularios */
        margin: 0 auto;
    }

    h1 {
        color: var(--primary-color);
        font-weight: 600;
        margin-bottom: 2rem;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 1rem;
    }

    /* 2. Alertas de Notificación */
    .alert {
        padding: 1rem;
        margin-bottom: 1.5rem;
        border-left: 4px solid;
        border-radius: 4px;
        list-style-position: inside;
    }

    .alert-danger {
        background-color: rgba(255, 59, 48, 0.1);
        border-left-color: var(--danger-color);
        color: var(--text-color);
    }
    .alert-danger ul {
        margin-top: 0.5rem;
        padding-left: 1rem;
    }

    /* 3. Estilos de Formularios y Tarjetas */
    .form-card, .detail-card {
        background-color: var(--surface-color);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr; /* Rejilla de dos columnas */
        gap: 1.5rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group.full-width {
        grid-column: 1 / -1; /* Ocupa todo el ancho */
    }

    label {
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--text-muted-color);
    }

    input, select {
        width: 100%;
        padding: 0.75rem;
        box-sizing: border-box;
        border: 1px solid var(--border-color);
        background-color: var(--background-color); /* Fondo más oscuro para contraste */
        color: var(--text-color);
        border-radius: 8px;
        font-size: 1rem;
    }

    input:focus, select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(0, 170, 255, 0.3);
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        background-color: var(--background-color);
        padding: 0.75rem;
        border-radius: 8px;
        border: 1px solid var(--border-color);
    }
    input[type="checkbox"] {
        width: 1.25em;
        height: 1.25em;
    }
    .checkbox-group label {
        margin: 0;
        color: var(--text-color);
        font-weight: normal;
    }

    /* 4. Botones */
    .form-actions {
        margin-top: 2rem;
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .button, .button-link {
        display: inline-block;
        padding: 0.75rem 1.5rem;
        background-color: var(--primary-color);
        color: white;
        border: none;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s ease, transform 0.2s ease;
    }

    .button:hover, .button-link:hover {
        background-color: #0088cc;
        transform: translateY(-2px);
    }

    .button-secondary {
        background-color: transparent;
        color: var(--primary-color);
        border: 1px solid var(--primary-color);
    }
    .button-secondary:hover {
        background-color: rgba(0, 170, 255, 0.1);
        color: var(--primary-color);
        transform: translateY(-2px);
    }

    /* 5. Estilos para la Página de Detalles */
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
</style>
</head>
<body>
    <div class="container">
        <h1>Detalle de la Reserva</h1>

        <div class="detail-card">
            <div class="detail-grid">
                <div class="detail-item">
                    <strong>ID Reserva</strong>
                    <span>#{{ $reserva->reserva_id }}</span>
                </div>
                <div class="detail-item">
                    <strong>Cliente</strong>
                    <span>{{ $reserva->cliente->nombre ?? '–' }}</span>
                </div>
                <div class="detail-item">
                    <strong>Cancha</strong>
                    <span>{{ $reserva->cancha->nombre ?? '–' }}</span>
                </div>
                <div class="detail-item">
                    <strong>Fecha</strong>
                    <span>{{ optional($reserva->fecha)->format('d/m/Y') ?? '–' }}</span>
                </div>
                <div class="detail-item">
                    <strong>Horario</strong>
                    <span>{{ $reserva->hora_inicio }} a {{ $reserva->hora_fin }}</span>
                </div>
                <div class="detail-item">
                    <strong>Monto Total</strong>
                    <span>{{ number_format($reserva->monto, 2) }} Bs.</span>
                </div>
                <div class="detail-item">
                    <strong>Estado</strong>
                    <span>{{ $reserva->estado }}</span>
                </div>
                <div class="detail-item">
                    <strong>Pago Completo</strong>
                    <span>{{ $reserva->pago_completo ? 'Sí' : 'No' }}</span>
                </div>
                 <div class="detail-item">
                    <strong>Método Pago</strong>
                    <span>{{ $reserva->metodo_pago ?? 'No especificado' }}</span>
                </div>
                <div class="detail-item">
                    <strong>Fecha de Creación</strong>
                    <span>{{ optional($reserva->created_at)->format('d/m/Y H:i') ?? '–' }}</span>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('reservas.index') }}" class="button-link button-secondary">Volver al listado</a>
            <a href="{{ route('reservas.edit', $reserva->reserva_id) }}" class="button-link">Editar Reserva</a>
        </div>
    </div>
</body>
</html>