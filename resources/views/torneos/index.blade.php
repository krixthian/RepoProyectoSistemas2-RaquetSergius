
@extends('layouts.app') 
@section('content')
<div class="container">
    <h1>Lista de Torneos</h1>
    <a href="{{ route('torneos.create') }}" class="btn btn-primary mb-3">Crear Nuevo Torneo</a>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Categoría</th>
                <th>Deporte</th>
                <th>Nº Equipos</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($torneos as $torneo)
                <tr>
                    <td>{{ $torneo->torneo_id }}</td>
                    <td>{{ $torneo->categoria }}</td>
                    <td>{{ $torneo->deporte }}</td>
                    <td>{{ $torneo->num_equipos }}</td>
                    <td>{{ $torneo->estado }}</td>
                    <td>
                        <a href="{{ route('torneos.show', $torneo) }}" class="btn btn-info btn-sm">Ver</a>
                        <a href="{{ route('torneos.edit', $torneo) }}" class="btn btn-warning btn-sm">Editar</a>
                        <form action="{{ route('torneos.destroy', $torneo) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que quieres eliminar este torneo?')">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No hay torneos registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection