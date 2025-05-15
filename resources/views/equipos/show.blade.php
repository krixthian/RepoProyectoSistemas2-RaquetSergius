@extends('layouts.app')

@section('title', 'Detalles del Equipo - ' . $equipo->nombre)

@section('content')
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h1>Detalles del Equipo: {{ $equipo->nombre }}</h1>
            </div>
            <div class="card-body">
                <p class="card-text"><strong>ID del Equipo:</strong> {{ $equipo->equipo_id }}</p>

                <h5 class="mt-4">Torneo Principal Asignado</h5>
                @if($equipo->torneoPrincipal)
                    <p class="card-text">
                        <strong>Nombre del Torneo:</strong> {{ $equipo->torneoPrincipal->deporte }} - {{ $equipo->torneoPrincipal->categoria }}<br>
                        <strong>ID del Torneo:</strong> {{ $equipo->torneoPrincipal->torneo_id }}
                        {{-- Puedes agregar más detalles del torneo si los tienes, ej: $equipo->torneoPrincipal->fecha_inicio --}}
                    </p>
                @else
                    <p class="card-text text-muted">Este equipo no tiene un torneo principal asignado directamente.</p>
                @endif

                <h5 class="mt-4">Capitán del Equipo</h5>
                @if($equipo->capitan)
                    <p class="card-text">
                        <strong>Nombre:</strong> {{ $equipo->capitan->nombre }} {{ $equipo->capitan->apellido ?? '' }}<br>
                        <strong>ID del Capitán:</strong> {{ $equipo->capitan->cliente_id }}
                        {{-- Puedes agregar más detalles del capitán si los tienes, ej: $equipo->capitan->email --}}
                    </p>
                @else
                    <p class="card-text text-muted">Este equipo no tiene un capitán asignado.</p>
                @endif

                {{-- Opcional: Mostrar otros torneos de la tabla pivote --}}
                @if($equipo->torneos && $equipo->torneos->count() > 0)
                    <h5 class="mt-4">Inscripciones Adicionales en Torneos (Tabla Pivote)</h5>
                    <ul class="list-group">
                        @foreach($equipo->torneos as $torneoInscrito)
                            {{-- Para no repetir si el torneo principal también está en la pivote --}}
                            @if(!$equipo->torneoPrincipal || $equipo->torneoPrincipal->torneo_id != $torneoInscrito->torneo_id)
                                <li class="list-group-item">
                                    {{ $torneoInscrito->deporte }} - {{ $torneoInscrito->categoria }}
                                    <small class="text-muted">(ID: {{ $torneoInscrito->torneo_id }})</small>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="card-footer text-muted">
                <a href="{{ route('equipos.edit', $equipo) }}" class="btn btn-warning"><i class="bi bi-pencil-square"></i> Editar Equipo</a>
                <form action="{{ route('equipos.destroy', $equipo) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('¿Estás seguro de eliminar este equipo?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Eliminar Equipo</button>
                </form>
                <a href="{{ route('equipos.index') }}" class="btn btn-secondary mt-3 mt-md-0 float-md-end"><i class="bi bi-list-ul"></i> Volver a la Lista</a>
            </div>
        </div>
    </div>
@endsection