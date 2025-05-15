@extends('layouts.app')

@section('title', 'Crear Nuevo Equipo')

@section('content')
    <h1>Crear Nuevo Equipo</h1>

    <form action="{{ route('equipos.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre del Equipo</label>
            <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ old('nombre') }}" required>
            @error('nombre')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="capitan_id" class="form-label">Capitán (Opcional)</label>
            <select class="form-select @error('capitan_id') is-invalid @enderror" id="capitan_id" name="capitan_id">
                <option value="">Seleccionar Capitán</option>
                @foreach($capitanes as $capitan)
                    {{-- Asumiendo que $capitan tiene 'cliente_id' y 'nombre' --}}
                    <option value="{{ $capitan->cliente_id }}" {{ old('capitan_id') == $capitan->cliente_id ? 'selected' : '' }}>{{ $capitan->nombre }} {{ $capitan->apellido ?? '' }}</option>
                @endforeach
            </select>
            @error('capitan_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- INICIO: Campo Nuevo para Torneo Principal --}}
<div class="mb-3">
    <label for="torneo_principal_id" class="form-label">Torneo Principal Asignado</label>
    <select class="form-select @error('torneo_principal_id') is-invalid @enderror" id="torneo_principal_id" name="torneo_principal_id" required>
        <option value="">Seleccione un Torneo</option>
        @foreach($torneos as $torneo)
            <option value="{{ $torneo->torneo_id }}" {{ old('torneo_principal_id') == $torneo->torneo_id ? 'selected' : '' }}>{{ $torneo->deporte }} - {{ $torneo->categoria }} (ID: {{ $torneo->torneo_id }})</option>
        @endforeach
    </select>
    @error('torneo_principal_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
{{-- FIN: Campo Nuevo para Torneo Principal --}}


        {{-- Opcional: Si quieres permitir asociar a OTRO torneo a través de la tabla pivote en el mismo formulario --}}
        {{-- <div class="mb-3">
            <label for="torneo_id_para_asociar_pivote" class="form-label">Inscribir Adicionalmente en Torneo (Pivote)</label>
            <select class="form-select @error('torneo_id_para_asociar_pivote') is-invalid @enderror" id="torneo_id_para_asociar_pivote" name="torneo_id_para_asociar_pivote">
                <option value="">Ninguno adicional</option>
                @foreach($torneos as $torneo)
                    <option value="{{ $torneo->torneo_id }}" {{ old('torneo_id_para_asociar_pivote') == $torneo->torneo_id ? 'selected' : '' }}>{{ $torneo->deporte }} - {{ $torneo->categoria }} (ID: {{ $torneo->torneo_id }})</option>
                @endforeach
            </select>
            @error('torneo_id_para_asociar_pivote')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div> --}}


        <button type="submit" class="btn btn-primary">Guardar Equipo</button>
        <a href="{{ route('equipos.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
@endsection