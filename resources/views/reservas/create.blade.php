<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Nueva Reserva</title>
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
        <h1>Crear Nueva Reserva</h1>

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>¡Error!</strong> Revisa los campos del formulario.
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('reservas.store') }}" method="POST">
            @csrf

            <div class="form-card">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="cliente_id">Cliente</label>
                        <select name="cliente_id" id="cliente_id" required>
                            <option value="">-- Selecciona un Cliente --</option>
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->cliente_id }}" {{ old('cliente_id') == $cliente->cliente_id ? 'selected' : '' }}>
                                    {{ $cliente->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="cancha_id">Cancha/Recurso</label>
                        <select name="cancha_id" id="cancha_id" required>
                            <option value="">-- Elige una cancha --</option>
                            @foreach ($canchas as $cancha)
                                <option value="{{ $cancha->cancha_id }}" {{ old('cancha_id') == $cancha->cancha_id ? 'selected' : '' }}>
                                    {{ $cancha->nombre }} {{ $cancha->tipo ? '('.$cancha->tipo.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fecha">Fecha de la Reserva</label>
                        <input type="date" name="fecha" id="fecha" value="{{ old('fecha') }}" min="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="monto">Monto Total Reserva (Bs.)</label>
                        <input type="number" step="0.01" min="0" name="monto" id="monto" value="{{ old('monto') }}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="hora_inicio">Hora de Inicio</label>
                        <input type="time" name="hora_inicio" id="hora_inicio" value="{{ old('hora_inicio') }}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="hora_fin">Hora de Fin</label>
                        <input type="time" name="hora_fin" id="hora_fin" value="{{ old('hora_fin') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select name="estado" id="estado" required>
                            <option value="Pendiente" selected>Pendiente</option>
                            <option value="Confirmada">Confirmada</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="metodo_pago">Método de Pago</label>
                        <select name="metodo_pago" id="metodo_pago">
                            <option value="">-- Opcional --</option>
                            <option value="QR">QR</option>
                            <option value="Efectivo">Efectivo</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                         <div class="checkbox-group">
                            <input type="hidden" name="pago_completo" value="0">
                            <input type="checkbox" name="pago_completo" id="pago_completo" value="1" {{ old('pago_completo') == '1' ? 'checked' : '' }}>
                            <label for="pago_completo">Marcar si el pago ya está completo.</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="button">Guardar Reserva</button>
                <a href="{{ route('reservas.index') }}" class="button-link button-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>