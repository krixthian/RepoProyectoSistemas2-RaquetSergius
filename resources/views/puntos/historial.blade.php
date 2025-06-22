@extends('layouts.app')

@section('title', 'Historial de Canjes de Premios')

@push('styles')
    <style>
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: var(--text-color);
            background-color: var(--surface-color);
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 0.95rem;
            vertical-align: top;
            border-top: 1px solid var(--border-color);
        }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid var(--border-color);
            background-color: var(--background-color);
            color: var(--text-muted-color);
            text-align: left;
        }

        .table tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <div class="welcome-message" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="color: var(--blueraquet-color);">Historial de Canjes</h1>
                <p>Aquí se muestran todos los premios que los clientes han canjeado.</p>
            </div>
            <a href="{{ route('clientes.opciones') }}" class="button" style="background-color: #6c757d;">Volver a Opciones</a>
        </div>

        <div class="table-responsive" style="margin-top: 2rem;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha y Hora del Canje</th>
                        <th>Cliente</th>
                        <th>Premio Canjeado</th>
                        <th>Puntos Utilizados</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($canjes as $canje)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($canje->fecha_canje)->format('d/m/Y H:i') }}</td>
                            <td>{{ $canje->cliente->nombre ?? 'Cliente no encontrado' }}</td>
                            <td>{{ $canje->premio->nombre ?? 'Premio no encontrado' }}</td>
                            <td>{{ $canje->puntos_utilizados }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center;">No hay canjes registrados hasta el momento.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">
            {{ $canjes->links() }}
        </div>

    </div>
@endsection
