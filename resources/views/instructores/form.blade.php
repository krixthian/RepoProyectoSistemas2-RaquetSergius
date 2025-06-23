<div class="form-card">
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>¡Ups!</strong> Hubo algunos problemas con tus datos.<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-group">
        <label for="nombre">Nombre Completo</label>
        <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $instructor->nombre ?? '') }}"
            class="form-control" required>
    </div>

    <div class="form-group">
        <label for="telefono">Teléfono</label>
        <input type="text" name="telefono" id="telefono" value="{{ old('telefono', $instructor->telefono ?? '') }}"
            class="form-control" required>
    </div>

    <div class="form-group">
        <label for="especialidad">Especialidad</label>
        <input type="text" name="especialidad" id="especialidad"
            value="{{ old('especialidad', $instructor->especialidad ?? '') }}" class="form-control" required
            placeholder="chicha, crossfit, cumbias, etc.">
    </div>

    <div class="form-group">
        <label for="tarifa_hora">Tarifa por Hora (Bs.)</label>
        <input type="number" name="tarifa_hora" id="tarifa_hora"
            value="{{ old('tarifa_hora', $instructor->tarifa_hora ?? '') }}" class="form-control" required
            step="0.01" min="0">
    </div>
</div>
