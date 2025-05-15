@extends('layouts.app')

@section('title', 'Gestión de Equipos')

@section('content')
    <h1>Gestión de Equipos</h1>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="mb-3">
        <a href="{{ route('equipos.create') }}" class="btn btn-primary">Crear Nuevo Equipo</a>
    </div>

    @if($equipos->isEmpty())
        <div class="alert alert-info">
            No hay equipos registrados actualmente.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Equipo</th>
                        <th>Torneo Principal</th>
                        <th>Capitán</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($equipos as $equipo)
                        <tr>
                            <td>{{ $equipo->equipo_id }}</td>
                            <td>{{ $equipo->nombre }}</td>
                            <td>
                                @if($equipo->torneoPrincipal)
                                    {{ $equipo->torneoPrincipal->deporte }} - {{ $equipo->torneoPrincipal->categoria }}
                                    <small class="text-muted">(ID: {{ $equipo->torneoPrincipal->torneo_id }})</small>
                                @else
                                    <span class="text-muted">Sin Torneo Asignado</span>
                                @endif
                            </td>
                            <td>
                                @if($equipo->capitan)
                                    {{ $equipo->capitan->nombre }} {{ $equipo->capitan->apellido ?? '' }}
                                @else
                                    <span class="text-muted">Sin Capitán</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('equipos.show', $equipo) }}" class="btn btn-sm btn-info" title="Ver Detalles"><i class="bi bi-eye"></i> Ver</a>
                                <a href="{{ route('equipos.edit', $equipo) }}" class="btn btn-sm btn-warning" title="Editar"><i class="bi bi-pencil-square"></i> Editar</a>
                                <form action="{{ route('equipos.destroy', $equipo) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('¿Estás realmente seguro de eliminar este equipo? Esta acción no se puede deshacer.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar"><i class="bi bi-trash"></i> Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection