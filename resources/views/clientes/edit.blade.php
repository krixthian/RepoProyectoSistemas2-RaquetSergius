@extends('layouts.app')

@section('content')
<style>
    body {
        background-color: #f5f5dc;
    }
    .card {
        background-color: #e0f7fa;
        border-radius: 20px;
        padding: 30px;
        max-width: 600px;
        margin: 40px auto;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .btn-custom {
        background-color: #00bcd4;
        color: white;
    }
    .btn-custom:hover {
        background-color: #0097a7;
    }
    label {
        font-weight: bold;
    }
</style>

<div class="card">
    <h3 class="text-center mb-4">Editar Cliente</h3>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('clientes.update', $cliente->cliente_id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="nombre">Nombre *</label>
            <input type="text" name="nombre" id="nombre" class="form-control" value="{{ old('nombre', $cliente->nombre) }}" required>
        </div>

        <div class="mb-3">
            <label for="telefono">Teléfono</label>
            <input type="text" name="telefono" id="telefono" class="form-control" value="{{ old('telefono', $cliente->telefono) }}">
</div>
    <div class="mb-3">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $cliente->email) }}">
    </div>

    <div class="form-check mb-3">
        <input type="checkbox" name="cliente_frecuente" id="cliente_frecuente" class="form-check-input" {{ old('cliente_frecuente', $cliente->cliente_frecuente) ? 'checked' : '' }}>
        <label class="form-check-label" for="cliente_frecuente">Cliente frecuente</label>
    </div>

    <div class="mb-3">
        <label for="puntos">Puntos</label>
        <input type="number" name="puntos" id="puntos" class="form-control" value="{{ old('puntos', $cliente->puntos) }}" min="0">
    </div>

    <button type="submit" class="btn btn-custom w-100">Actualizar Cliente</button>
    <a href="{{ route('clientes.index') }}" class="btn btn-secondary mt-2 w-100">Cancelar</a>
</form>


