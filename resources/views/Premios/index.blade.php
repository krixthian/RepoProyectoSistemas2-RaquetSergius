@extends('layouts.app')

@section('content')
<h1 style="color: #fff; margin-bottom: 20px;">Listado de Premios</h1>

<a href="{{ route('premios.create') }}"
   style="padding: 10px 20px; background: #007bff; color: #fff; border: none; border-radius: 5px; font-weight: bold; margin-bottom: 20px; display: inline-block">
    Crear nuevo
</a>

<!-- Buscador -->
<form method="GET" action="{{ route('premios.index') }}" style="margin-bottom: 20px;">
    <input type="text" name="search" placeholder="Buscar por nombre..." 
           value="{{ request('search') }}"
           style="padding: 10px; background: #333; color: #fff; border: 1px solid #007bff; border-radius: 5px; width: 250px;">
    <button type="submit" 
            style="padding: 10px 20px; background: #007bff; color: #fff; border: none; border-radius: 5px; font-weight: bold;">
        Buscar
    </button>
</form>

<table style="width: 100%; background: #222; color: #fff; border: 2px solid #007bff; border-radius: 10px; overflow: hidden">
    <thead style="background: #007bff">
        <tr>
            <th style="padding: 10px; color: #fff;">ID</th>
            <th style="padding: 10px; color: #fff;">Nombre</th>
            <th style="padding: 10px; color: #fff;">Puntos</th>
            <th style="padding: 10px; color: #fff;">Tipo</th>
            <th style="padding: 10px; color: #fff;">Activo</th>
            <th style="padding: 10px; color: #fff;">Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($premios as $premio)
        <tr style="border-bottom: 1px solid #007bff">
            <td style="padding: 10px; color: #fff;">{{ $premio->premio_id }}</td>
            <td style="padding: 10px; color: #fff;">{{ $premio->nombre }}</td>
            <td style="padding: 10px; color: #fff;">{{ $premio->puntos_requeridos }}</td>
            <td style="padding: 10px; color: #fff;">{{ $premio->tipo }}</td>
            <td style="padding: 10px; color: #fff;">
                <input type="checkbox" disabled {{ $premio->activo ? 'checked' : '' }}>
            </td>
            <td style="padding: 10px">
                <a href="{{ route('premios.edit',$premio) }}"
                   style="padding: 5px 10px; background: #007bff; color: #fff; border: none; border-radius: 5px; margin-right: 5px">
                   Editar
                </a>
                <form action="{{ route('premios.destroy',$premio) }}"
                      method="POST" style="display: inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            style="padding: 5px 10px; background: #ff4d4d; color: #fff; border: none; border-radius: 5px">
                        Eliminar
                    </button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align: center; padding: 20px; color: #ccc;">
                No se encontraron premios con ese nombre.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection
