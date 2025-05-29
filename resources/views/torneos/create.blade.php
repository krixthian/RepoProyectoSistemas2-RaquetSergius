@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Crear Nuevo Torneo</h1>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('torneos.store') }}" method="POST">
            @csrf {{-- Directiva Blade para protección CSRF --}}

            <div class="form-group mb-3">
                <label for="evento_id">Evento:</label>
                <select name="evento_id" id="evento_id" class="form-control" required>
                    <option value="">Seleccionar Evento</option>
                    @foreach($eventos as $evento)
                        <option value="{{ $evento->evento_id }}" {{ old('evento_id') == $evento->evento_id ? 'selected' : '' }}>
                            {{ $evento->nombre_del_evento ?? 'Evento ID: ' . $evento->evento_id }}</option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Selecciona el evento al que pertenece este torneo.</small>
            </div>

            <div class="form-group mb-3">
                <label for="deporte">Deporte:</label>
                <input type="text" name="deporte" id="deporte" class="form-control" value="voleibol" readonly>
                <small class="form-text text-muted">El deporte para este torneo es voleibol.</small>
            </div>

            <div class="form-group mb-3">
                <label for="categoria">Categoría:</label>
                <select name="categoria" id="categoria" class="form-control" required>
                    <option value="">Seleccionar Categoría</option>
                    <option value="Juvenil" {{ old('categoria') == 'Juvenil' ? 'selected' : '' }}>Juvenil</option>
                    <option value="Infantil" {{ old('categoria') == 'Infantil' ? 'selected' : '' }}>Infantil</option>
                    <option value="Universitario" {{ old('categoria') == 'Universitario' ? 'selected' : '' }}>Universitario
                    </option>
                </select>
            </div>


            <div class="form-group mb-3">
                <label for="num_equipos">Número de Equipos Esperados:</label>
                <input type="number" name="num_equipos" id="num_equipos" class="form-control"
                    value="{{ old('num_equipos') }}" required min="2" max="20">
                <small class="form-text text-muted">Indica el número esperado de equipos para este torneo.</small>
            </div>

            <div class="form-group mb-3">
                <label for="estado">Estado:</label>
                <select name="estado" id="estado" class="form-control" required>
                    <option value="Pendiente" {{ old('estado') == 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="En Curso" {{ old('estado') == 'En Curso' ? 'selected' : '' }}>En Curso</option>
                    <option value="Finalizado" {{ old('estado') == 'Finalizado' ? 'selected' : '' }}>Finalizado</option>
                    <option value="Cancelado" {{ old('estado') == 'Cancelado' ? 'selected' : '' }}>Cancelado</option>
                </select>
            </div>

            <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> Guardar Torneo</button>
            <a href="{{ route('torneos.index') }}" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Cancelar</a>
        </form>
    </div>
@endsection