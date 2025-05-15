@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Detalles del Torneo</h1>

    <div class="card">
        <div class="card-header">
            Torneo ID: {{ $torneo->torneo_id }}
        </div>
        <div class="card-body">
            <h5 class="card-title">{{ $torneo->categoria }} - {{ $torneo->deporte }}</h5>
            <p class="card-text"><strong>Evento ID:</strong> {{ $torneo->evento_id }}</p>
            {{-- <p class="card-text"><strong>Nombre del Evento:</strong> {{ $torneo->evento->nombre_evento ?? 'No especificado' }}</p> --}}
            <p class="card-text"><strong>Número de Equipos:</strong> {{ $torneo->num_equipos }}</p>
            <p class="card-text"><strong>Estado:</strong> {{ $torneo->estado }}</p>
            <p class="card-text"><strong>Creado el:</strong> {{ $torneo->created_at->format('d/m/Y H:i') }}</p>
            <p class="card-text"><strong>Última actualización:</strong> {{ $torneo->updated_at->format('d/m/Y H:i') }}</p>

            {{-- <h5>Equipos:</h5> --}}
            {{-- @if($torneo->equipos && $torneo->equipos->count() > 0) --}}
            {{--    <ul> --}}
            {{--        @foreach($torneo->equipos as $equipo) --}}
            {{--            <li>{{ $equipo->nombre_equipo }}</li> --}}
            {{--        @endforeach --}}
            {{--    </ul> --}}
            {{-- @else --}}
            {{--    <p>No hay equipos registrados para este torneo aún.</p> --}}
            {{-- @endif --}}
        </div>
        <div class="card-footer">
            <a href="{{ route('torneos.edit', $torneo) }}" class="btn btn-warning">Editar</a>
            <a href="{{ route('torneos.index') }}" class="btn btn-secondary">Volver a la lista</a>
        </div>
    </div>
</div>
@endsection