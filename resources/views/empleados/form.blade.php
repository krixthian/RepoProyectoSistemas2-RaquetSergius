@csrf

<div class="mb-3">
    <label for="nombre" class="form-label fw-bold">Nombre</label>
    <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $empleado->nombre ?? '') }}" required>
</div>

<div class="mb-3">
    <label for="usuario" class="form-label fw-bold">Usuario</label>
    <input type="text" name="usuario" class="form-control" value="{{ old('usuario', $empleado->usuario ?? '') }}" required>
</div>

<div class="mb-3">
    <label for="email" class="form-label fw-bold">Email</label>
    <input type="email" name="email" class="form-control" value="{{ old('email', $empleado->email ?? '') }}">
</div>

<div class="mb-3">
    <label for="telefono" class="form-label fw-bold">Teléfono</label>
    <input type="text" name="telefono" class="form-control" value="{{ old('telefono', $empleado->telefono ?? '') }}">
</div>

<div class="mb-3">
    <label for="rol" class="form-label fw-bold">Rol</label>
    <select name="rol" class="form-select">
        <option value="admin" {{ (old('rol', $empleado->rol ?? '') == 'admin') ? 'selected' : '' }}>Admin</option>
        <option value="vendedor" {{ (old('rol', $empleado->rol ?? '') == 'vendedor') ? 'selected' : '' }}>Vendedor</option>
    </select>
</div>

<div class="mb-3 form-check">
    <input type="checkbox" name="activo" class="form-check-input" id="activo" {{ old('activo', $empleado->activo ?? false) ? 'checked' : '' }}>
    <label class="form-check-label" for="activo">Activo</label>
</div>

@if (!isset($empleado))
<div class="mb-3">
    <label for="contrasena" class="form-label fw-bold">Contraseña</label>
    <input type="password" name="contrasena" class="form-control" required>
</div>
@endif

<button type="submit" class="btn btn-success">Guardar</button>
<a href="{{ route('empleados.index') }}" class="btn btn-secondary">Cancelar</a>