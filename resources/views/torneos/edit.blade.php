@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar Torneo: {{ $torneo->categoria }} ({{ $torneo->deporte }})</h1>

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

    <form action="{{ route('torneos.update', $torneo) }}" method="POST">
        @csrf
        @method('PUT') {{-- Método HTTP para actualizar --}}

        <div class="form-group mb-3">
            <label for="evento_id">ID Evento:</label>
            {{-- <select name="evento_id" id="evento_id" class="form-control" required> --}}
            {{--    @foreach($eventos as $evento) --}}
            {{--        <option value="{{ $evento->evento_id }}" {{ old('evento_id', $torneo->evento_id) == $evento->evento_id ? 'selected' : '' }}>{{ $evento->nombre }}</option> --}}
            {{--    @endforeach --}}
            {{-- </select> --}}
            <input type="number" name="evento_id" id="evento_id" class="form-control" value="{{ old('evento_id', $torneo->evento_id) }}" required>
        </div>

        <div class="form-group mb-3">
            <label for="categoria">Categoría:</label>
            <input type="text" name="categoria" id="categoria" class="form-control" value="{{ old('categoria', $torneo->categoria) }}" required>
        </div>

        <div class="form-group mb-3">
            <label for="num_equipos">Número de Equipos:</label>
            <input type="number" name="num_equipos" id="num_equipos" class="form-control" value="{{ old('num_equipos', $torneo->num_equipos) }}" required min="2">
        </div>

         <div class="form-group mb-3">
            <label for="deporte">Deporte:</label>
            <input type="text" name="deporte" id="deporte" class="form-control" value="{{ old('deporte', $torneo->deporte) }}" required>
        </div>

        <div class="form-group mb-3">
            <label for="estado">Estado:</label>
            <select name="estado" id="estado" class="form-control" required>
                <option value="Pendiente" {{ old('estado', $torneo->estado) == 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="En Curso" {{ old('estado', $torneo->estado) == 'En Curso' ? 'selected' : '' }}>En Curso</option>
                <option value="Finalizado" {{ old('estado', $torneo->estado) == 'Finalizado' ? 'selected' : '' }}>Finalizado</option>
                <option value="Cancelado" {{ old('estado', $torneo->estado) == 'Cancelado' ? 'selected' : '' }}>Cancelado</option>
            </select>
        </div>

        <button type="submit" class="btn btn-success">Actualizar Torneo</button>
        <a href="{{ route('torneos.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection