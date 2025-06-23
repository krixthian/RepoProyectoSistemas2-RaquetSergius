@extends('layouts.app')

@section('content')
@push('styles')
<link href="{{ asset('css/styleEmpleados.css') }}" rel="stylesheet">
@endpush

<div class="container mt-5">
    <div class="card p-4">
        <header class="page-header">
    <div class="header-info">
        <h1><i class="fas fa-user-friends me-2"></i>Gestión de Clientes</h1>
    </div>
    <a href="{{ route('clientes.create') }}" class="btn-primary btn-new-employee">
        <i class="fas fa-plus-circle me-2"></i>Nuevo Cliente
    </a>
</header>
<!-- Buscador por teléfono -->
<form method="GET" action="{{ route('clientes.index') }}" style="margin: 20px 0; display: flex; gap: 10px; align-items: center;">
    <input type="text" name="telefono" placeholder="Buscar por teléfono..."
           value="{{ request('telefono') }}"
           style="padding: 10px; background: #333; color: #fff; border: 1px solid #007bff; border-radius: 5px; width: 250px;">
    <button type="submit"
            style="padding: 10px 20px; background: #007bff; color: #fff; border: none; border-radius: 5px; font-weight: bold;">
        Buscar
    </button>
</form>

            <div class="card-header">
                <h2><i class="fas fa-list-ol me-2"></i>Listado Completo</h2>
            </div>
@if(session('success'))
    <div class="alert alert-success mt-3">{{ session('success') }}</div>
@endif


        <table class="table table-hover table-bordered">
            <table class="employees-table">
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
        </table>
    </div>
</div>
@endsection
