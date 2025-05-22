@extends('layouts.app')

@section('title', 'Editar Equipo - ' . $equipo->nombre)

@section('content')
    <div class="container mt-4">
        <h1>Editar Equipo: {{ $equipo->nombre }}</h1>

        <form action="{{ route('equipos.update', $equipo) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre del Equipo</label>
                <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ old('nombre', $equipo->nombre) }}" required>
                @error('nombre')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="capitan_id" class="form-label">Capitán (Opcional)</label>
                <select class="form-select @error('capitan_id') is-invalid @enderror" id="capitan_id" name="capitan_id">
                    <option value="">Seleccionar Capitán</option>
                    @foreach($capitanes as $capitan)
                        <option value="{{ $capitan->cliente_id }}" {{ old('capitan_id', $equipo->capitan_id) == $capitan->cliente_id ? 'selected' : '' }}>
                            {{ $capitan->nombre }} {{ $capitan->apellido ?? '' }}
                        </option>
                    @endforeach
                </select>
                @error('capitan_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="torneo_principal_id" class="form-label">Torneo Principal Asignado</label>
                <select class="form-select @error('torneo_principal_id') is-invalid @enderror" id="torneo_principal_id" name="torneo_principal_id" required>
                    <option value="">Seleccione un Torneo</option>
                    @foreach($torneos as $torneo)
                        <option value="{{ $torneo->torneo_id }}" {{ old('torneo_principal_id', $equipo->torneo_id) == $torneo->torneo_id ? 'selected' : '' }}>
                            {{ $torneo->deporte }} - {{ $torneo->categoria }} (ID: {{ $torneo->torneo_id }})
                        </option>
                    @endforeach
                </select>
                @error('torneo_principal_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            {{-- FIN: Campo para editar el Torneo Principal --}}


            {{-- Opcional: Si quieres manejar también la inscripción a un torneo adicional vía pivote en la edición --}}
            {{-- <div class="mb-3">
                <label for="torneo_id_para_asociar_pivote" class="form-label">Inscribir Adicionalmente en Torneo (Pivote)</label>
                <select class="form-select @error('torneo_id_para_asociar_pivote') is-invalid @enderror" id="torneo_id_para_asociar_pivote" name="torneo_id_para_asociar_pivote">
                    <option value="">Ninguno adicional</option>
                    @php
                        // Para preseleccionar el torneo de la tabla pivote, si existe y es único.
                        // Si un equipo puede estar en MUCHOS torneos vía pivote, necesitarías un multiselect.
                        $torneoPivotSeleccionadoId = $equipo->torneos->isNotEmpty() ? $equipo->torneos->first()->torneo_id : null;
                        // Evitar que se pueda seleccionar el mismo torneo principal en la pivote aquí
                        if ($torneoPivotSeleccionadoId == $equipo->torneo_id) {
                            $torneoPivotSeleccionadoId = null; // O alguna otra lógica
                        }
                    @endphp
                    @foreach($torneos as $torneo)
                        @if($torneo->torneo_id != $equipo->torneo_id) // No mostrar el torneo principal en esta lista
                            <option value="{{ $torneo->torneo_id }}" {{ old('torneo_id_para_asociar_pivote', $torneoPivotSeleccionadoId) == $torneo->torneo_id ? 'selected' : '' }}>
                                {{ $torneo->deporte }} - {{ $torneo->categoria }} (ID: {{ $torneo->torneo_id }})
                            </option>
                        @endif
                    @endforeach
                </select>
                @error('torneo_id_para_asociar_pivote')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div> --}}

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Actualizar Equipo</button>
                <a href="{{ route('equipos.index') }}" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Cancelar</a>
            </div>
        </form>
    </div>
@endsection