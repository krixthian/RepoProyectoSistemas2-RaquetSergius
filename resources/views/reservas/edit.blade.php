<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Reserva #{{ $reserva->reserva_id }}</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 20px;
            background-color: #2C3844;
            color: #ADBCB9;
        }
        h1 {
            color: #59FFD8;
            border-bottom: 2px solid #59FFD8;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }
        .form-group { margin-bottom: 18px; }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #ADBCB9;
        }
        input, select {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #ADBCB9;
            background-color: #2C3844;
            color: white;
            border-radius: 4px;
        }
        .alert { padding:15px; margin-bottom:20px; border-radius:4px; }
        .alert-danger { color:#a94442; background:#f2dede; border:1px solid #ebccd1; }
        button {
            padding:10px 20px;
            background:#59FFD8;
            color:#2C3844;
            border:none;
            border-radius:4px;
            font-weight:bold;
            text-transform:uppercase;
            cursor:pointer;
            transition: background 0.2s;
        }
        button:hover { background:#ADBCB9; }
        a.cancel {
            margin-left:15px;
            color:#59FFD8;
            text-decoration:none;
        }
        a.cancel:hover { color:#ADBCB9; }
        fieldset {
            border:1px solid #ADBCB9;
            padding:20px;
            margin-bottom:25px;
            border-radius:4px;
        }
        legend {
            font-weight:bold;
            padding:0 10px;
            color:#59FFD8;
        }
        input[type=checkbox] { width:auto; vertical-align:middle; }
        input[type=checkbox] + label { display:inline; margin-left:5px; font-weight:normal; color:#ADBCB9; }
    </style>
</head>
<body>
    @php use Carbon\Carbon; @endphp

    <h1>Editar Reserva #{{ $reserva->reserva_id }}</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>¡Error!</strong> Revisa los campos marcados.
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('reservas.update', $reserva->reserva_id) }}" method="POST">
        @csrf
        @method('PUT')

        <fieldset>
            <legend>Datos de la Reserva</legend>

            <div class="form-group">
                <label for="cliente_id">Cliente:</label>
                <select name="cliente_id" id="cliente_id" required>
                    <option value="">-- Selecciona un Cliente --</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->cliente_id }}"
                            {{ old('cliente_id', $reserva->cliente_id) == $cliente->cliente_id ? 'selected' : '' }}>
                            {{ $cliente->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="cancha_id">Cancha/Recurso:</label>
                <select name="cancha_id" id="cancha_id" required>
                    <option value="">-- Selecciona una Cancha --</option>
                    @foreach ($canchas as $cancha)
                        <option value="{{ $cancha->cancha_id }}"
                            {{ old('cancha_id', $reserva->cancha_id) == $cancha->cancha_id ? 'selected' : '' }}>
                            {{ $cancha->nombre }} {{ $cancha->tipo ? '('.$cancha->tipo.')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="fecha">Fecha:</label>
                <input type="date" name="fecha" id="fecha" value="{{ old('fecha') }}" min="{{ date('Y-m-d') }}" required>

            </div>

            <div class="form-group">
                <label for="hora_inicio">Hora de Inicio:</label>
                <input type="time" name="hora_inicio" id="hora_inicio"
                       value="{{ old('hora_inicio', $reserva->hora_inicio
                           ? Carbon::parse($reserva->hora_inicio)->format('H:i')
                           : '') }}"
                       required>
            </div>

            <div class="form-group">
                <label for="hora_fin">Hora de Fin:</label>
                <input type="time" name="hora_fin" id="hora_fin"
                       value="{{ old('hora_fin', $reserva->hora_fin
                           ? Carbon::parse($reserva->hora_fin)->format('H:i')
                           : '') }}"
                       required>
            </div>

            <div class="form-group">
                <label for="monto">Monto Total (Bs.):</label>
                <input type="number" step="0.01" min="0" max="90" name="monto" id="monto"
                       value="{{ old('monto', $reserva->monto) }}" required>
            </div>

            <div class="form-group">
                <label for="estado">Estado:</label>
                <select name="estado" id="estado" required>
                    @foreach(['Pendiente','Confirmada','Cancelada','Completada'] as $estado)
                        <option value="{{ $estado }}"
                            {{ old('estado', $reserva->estado) == $estado ? 'selected' : '' }}>
                            {{ $estado }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="metodo_pago">Método de Pago:</label>
                <select name="metodo_pago" id="metodo_pago">
                    <option value="">-- Opcional --</option>
                    @foreach(['QR','Efectivo','Tarjeta','Transferencia','Otro'] as $mp)
                        <option value="{{ $mp }}"
                            {{ old('metodo_pago', $reserva->metodo_pago) == $mp ? 'selected' : '' }}>
                            {{ $mp }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <input type="hidden" name="pago_completo" value="0">
                <input type="checkbox" name="pago_completo" id="pago_completo" value="1"
                       {{ old('pago_completo', $reserva->pago_completo) ? 'checked' : '' }}>
                <label for="pago_completo">Pago Completo</label>
            </div>
        </fieldset>

        <button type="submit">Actualizar Reserva</button>
        <a href="{{ route('reservas.index') }}" class="cancel">Cancelar</a>
    </form>
</body>
</html>
