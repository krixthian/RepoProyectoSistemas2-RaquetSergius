<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Nueva Reserva</title>
    <style>
    body {
        font-family: sans-serif;
        margin: 20px;
        background-color: #2C3844;
        color: #ADBCB9;
    }

    h1 {
        text-align: center;
        color: #59FFD8;
    }

    .form-group {
        margin-bottom: 15px;
    }

    label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #59FFD8;
    }

    input[type="text"], input[type="date"], input[type="time"], input[type="number"], select {
        width: 100%;
        padding: 8px;
        box-sizing: border-box;
        border: 1px solid #ADBCB9;
        background-color: #2C3844;
        color: white;
        border-radius: 4px;
    }

    /* Específico para checkbox */
    input[type="checkbox"] {
        width: auto; /* Ancho automático para checkbox */
        display: inline-block; /* Mostrar en línea con la etiqueta */
        margin-left: 5px;
        vertical-align: middle; /* Alinear verticalmente */
    }
    label[for="pago_completo"] { /* Estilo para la etiqueta del checkbox */
        display:inline;
        font-weight:normal;
        vertical-align: middle; /* Alinear verticalmente */
    }


    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }

    .alert-danger {
        color: #a94442;
        background-color: #f2dede;
        border-color: #ebccd1;
    }

    button {
        padding: 10px 15px;
        background-color: #59FFD8;
        color: #2C3844;
        border: none;
        cursor: pointer;
        border-radius: 4px;
        font-weight: bold;
    }

    button:hover {
        background-color: #48E5C2;
    }

    fieldset {
        border: 1px solid #ADBCB9;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 5px;
    }

    legend {
        font-weight: bold;
        padding: 0 5px;
        color: #59FFD8;
    }

    a {
        color: #59FFD8;
        text-decoration: none;
        margin-left: 10px; /* Añadido para separar del botón */
    }

    a:hover {
        text-decoration: underline;
    }
    small { /* Estilo para texto pequeño o ayuda */
        display: block;
        margin-top: 4px;
        color: #ADBCB9;
    }
    </style>

</head>
<body>
    <h1>Crear Nueva Reserva</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>¡Error!</strong> Revisa los campos marcados.<br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('reservas.store') }}" method="POST">
        @csrf

        <fieldset>
            <legend>Datos de la Reserva</legend>

            <div class="form-group">
                <label for="cliente_id">Cliente:</label>
                <select name="cliente_id" id="cliente_id" required>
                    <option value="">-- Selecciona un Cliente --</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->cliente_id }}" {{ old('cliente_id') == $cliente->cliente_id ? 'selected' : '' }}>
                            {{ $cliente->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="cancha_id">Cancha/Recurso:</label>
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
                <label for="fecha">Fecha de la Reserva:</label>
                <input type="date" name="fecha" id="fecha" value="{{ old('fecha') }}" required>
            </div>

            <div class="form-group">
                <label for="hora_inicio">Hora de Inicio:</label>
                <input type="time" name="hora_inicio" id="hora_inicio" value="{{ old('hora_inicio') }}" required>
            </div>

            <div class="form-group">
                <label for="hora_fin">Hora de Fin:</label>
                <input type="time" name="hora_fin" id="hora_fin" value="{{ old('hora_fin') }}" required>
            </div>

             <div class="form-group">
                <label for="monto">Monto Total Reserva (Bs.):</label>
                <input type="number" step="0.01" min="0" name="monto" id="monto" value="{{ old('monto') }}" required>
            </div>

             <div class="form-group">
                <label for="estado">Estado:</label>
                <select name="estado" id="estado" required>
                    <option value="Pendiente" {{ old('estado', 'Pendiente') == 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="Confirmada" {{ old('estado') == 'Confirmada' ? 'selected' : '' }}>Confirmada</option>
                    <option value="Cancelada" {{ old('estado') == 'Cancelada' ? 'selected' : '' }}>Cancelada</option>
                    <option value="Completada" {{ old('estado') == 'Completada' ? 'selected' : '' }}>Completada</option>
                    </select>
            </div>

             <div class="form-group">
                <label for="metodo_pago">Método de Pago:</label>
                 <select name="metodo_pago" id="metodo_pago" required> <option value="QR" {{ old('metodo_pago') == 'QR' ? 'selected' : '' }}>QR</option>
                     <option value="Efectivo" {{ old('metodo_pago') == 'Efectivo' ? 'selected' : '' }}>Efectivo</option>
                     </select>
            </div>

             <div class="form-group">
                 <label for="pago_completo">¿Pago Completo?</label>
                 <input type="hidden" name="pago_completo" value="0"> <input type="checkbox" name="pago_completo" id="pago_completo" value="1" {{ old('pago_completo', '0') == '1' ? 'checked' : '' }}>
                 <label for="pago_completo">Marcar si el pago está completo</label> </div>
        </fieldset>

        <button type="submit">Guardar Reserva</button>
        <a href="{{ route('reservas.index') }}">Cancelar</a>
    </form>

</body>
</html>