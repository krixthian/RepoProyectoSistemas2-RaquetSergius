@extends('layouts.app')

@section('content')
<style>
    body {
        background-color: #f5f5dc; /* beige */
    }
    .card {
        background-color: #e0f7fa; /* celeste piscina */
        border-radius: 20px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    .btn-custom {
        background-color: #00bcd4; /* celeste piscina */
        color: white;
    }
    .btn-custom:hover {
        background-color: #0097a7;
    }
    .table th {
        background-color: #b2ebf2;
    }
</style>

<div class="container mt-5">
    <div class="card p-4">
        <h2 class="mb-4 text-center">Gestión de Clientes</h2>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <a href="{{ route('clientes.create') }}" class="btn btn-custom mb-3">+ Nuevo Cliente</a>

        <table class="table table-hover table-bordered">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Frecuente</th>
                    <th>Puntos</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clientes as $cliente)
                <tr>
                    <td>{{ $cliente->nombre }}</td>
                    <td>{{ $cliente->telefono }}</td>
                    <td>{{ $cliente->email }}</td>
                    <td>{{ $cliente->cliente_frecuente ? 'Sí' : 'No' }}</td>
                    <td>{{ $cliente->puntos }}</td>
                    <td>
                        <a href="{{ route('clientes.edit', $cliente->cliente_id) }}" class="btn btn-sm btn-warning">Editar</a>
                        <form action="{{ route('clientes.destroy', $cliente->cliente_id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que deseas eliminar este cliente?')">Eliminar</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center">No hay clientes registrados aún.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
