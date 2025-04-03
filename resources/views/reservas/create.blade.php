<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Nueva Reserva</title>
    <style>
        body { font-family: sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="datetime-local"], input[type="number"], select { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; }
        .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
        .alert-danger { color: #a94442; background-color: #f2dede; border-color: #ebccd1; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px; }
        fieldset { border: 1px solid #ccc; padding: 15px; margin-bottom: 15px; }
        legend { font-weight: bold; padding: 0 5px; }
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
                        {{-- CORRECCIÓN: Usar cliente_id como value --}}
                        <option value="{{ $cliente->cliente_id }}" {{ old('cliente_id') == $cliente->cliente_id ? 'selected' : '' }}>
                            {{ $cliente->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="fecha_hora_inicio">Fecha y Hora de Inicio:</label>
                <input type="datetime-local" name="fecha_hora_inicio" id="fecha_hora_inicio" value="{{ old('fecha_hora_inicio') }}" required>
            </div>

            <div class="form-group">
                <label for="fecha_hora_fin">Fecha y Hora de Fin:</label>
                <input type="datetime-local" name="fecha_hora_fin" id="fecha_hora_fin" value="{{ old('fecha_hora_fin') }}" required>
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
                 <select name="metodo_pago" id="metodo_pago">
                     <option value="" {{ old('metodo_pago') == '' ? 'selected' : '' }}>-- Opcional --</option>
                     <option value="Efectivo" {{ old('metodo_pago') == 'Efectivo' ? 'selected' : '' }}>Efectivo</option>
                     <option value="Tarjeta" {{ old('metodo_pago') == 'Tarjeta' ? 'selected' : '' }}>Tarjeta</option>
                     <option value="Transferencia" {{ old('metodo_pago') == 'Transferencia' ? 'selected' : '' }}>Transferencia</option>
                     <option value="Otro" {{ old('metodo_pago') == 'Otro' ? 'selected' : '' }}>Otro</option>
                 </select>
            </div>

             <div class="form-group">
                 <label for="pago_completo">¿Pago Completo?</label>
                 {{-- Truco común para enviar 0 si el checkbox no está marcado --}}
                 <input type="hidden" name="pago_completo" value="0">
                 <input type="checkbox" name="pago_completo" id="pago_completo" value="1" {{ old('pago_completo', '0') == '1' ? 'checked' : '' }} style="width: auto; display: inline-block; margin-left: 5px;">
                 <label for="pago_completo" style="display:inline; font-weight:normal;">Marcar si el pago está completo</label>
             </div>
        </fieldset>

        <fieldset>
            <legend>Asignar Cancha (Para esta Reserva)</legend>
             <div class="form-group">
                <label for="cancha_id">Seleccionar Cancha/Recurso:</label>
                <select name="cancha_id" id="cancha_id" required>
                    <option value="">-- Elige una cancha --</option>
                    @foreach ($canchas as $cancha)
                        {{-- CORRECCIÓN: Usar cancha_id como value --}}
                        <option value="{{ $cancha->cancha_id }}" {{ old('cancha_id') == $cancha->cancha_id ? 'selected' : '' }}>
                            {{ $cancha->nombre }} {{ $cancha->tipo ? '('.$cancha->tipo.')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

             <div class="form-group">
                <label for="precio_total_cancha">Precio Específico para esta Cancha (Bs.):</label>
                <input type="number" step="0.01" min="0" name="precio_total_cancha" id="precio_total_cancha" value="{{ old('precio_total_cancha') }}" required>
                <small>Este es el precio asociado solo a esta cancha dentro de la reserva.</small>
            </div>
        </fieldset>

        <button type="submit">Guardar Reserva</button>
        <a href="{{ route('reservas.index') }}" style="margin-left: 10px;">Cancelar</a>
    </form>

</body>
</html>