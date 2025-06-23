@extends('layouts.app')

@section('title', 'Historial de Movimientos de Puntos')

@push('styles')
    <style>
        .filter-card {
            background-color: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        label {
            font-weight: 500;
            color: var(--text-muted-color);
            margin-bottom: 0.5rem;
            display: block;
        }

        input {
            width: 100%;
            padding: 0.75rem;
            box-sizing: border-box;
            border: 1px solid var(--border-color);
            background-color: var(--background-color);
            color: var(--text-color);
            border-radius: 8px;
            font-size: 1rem;
        }

        .filter-actions {
            display: flex;
            gap: 0.5rem;
        }

        .button {
            padding: 0.75rem 1.5rem;
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
        }

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
            vertical-align: middle;
            border-top: 1px solid var(--border-color);
        }

        .table thead th {
            border-bottom: 2px solid var(--border-color);
            background-color: var(--background-color);
            color: var(--text-muted-color);
            text-align: left;
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
                <h1 style="color: var(--blueraquet-color);">Log de Movimientos de Puntos</h1>
                <p>Audita todos los movimientos de puntos de los clientes.</p>
            </div>
            <a href="{{ route('clientes.opciones') }}" class="button" style="background-color: #6c757d;">Volver a Opciones</a>
        </div>

        <div class="filter-card">
            <form action="{{ route('puntos.log.historial') }}" method="GET" class="filter-form">
                <div class="form-group">
                    <label for="nombre_cliente">Buscar por Nombre de Cliente</label>
                    <input type="text" name="nombre_cliente" id="nombre_cliente"
                        value="{{ $filters['nombre_cliente'] ?? '' }}" placeholder="Nombre del cliente">
                </div>
                <div class="form-group">
                    <label for="telefono_cliente">Buscar por Teléfono</label>
                    <input type="text" name="telefono_cliente" id="telefono_cliente"
                        value="{{ $filters['telefono_cliente'] ?? '' }}" placeholder="Número de teléfono">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="button">Filtrar</button>
                    <a href="{{ route('puntos.log.historial') }}" class="button"
                        style="background-color: #6c757d;">Limpiar</a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Acción</th>
                        <th>Detalle</th>
                        <th>Cambio</th>
                        <th>Puntos Antes</th>
                        <th>Puntos Después</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($log->fecha)->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->cliente->nombre ?? 'N/A' }}</td>
                            <td>{{ $log->cliente->telefono ?? 'N/A' }}</td>
                            <td>{{ $log->accion }}</td>
                            <td>{{ $log->detalle }}</td>
                            <td>
                                @if ($log->puntos_cambio > 0)
                                    <span style="color: green; font-weight: bold;">+{{ $log->puntos_cambio }}</span>
                                @else
                                    <span style="color: red; font-weight: bold;">{{ $log->puntos_cambio }}</span>
                                @endif
                            </td>
                            <td>{{ $log->puntos_antes }}</td>
                            <td>{{ $log->puntos_despues }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center;">No se encontraron registros con los filtros
                                aplicados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">
            {{ $logs->links() }}
        </div>

    </div>
@endsection
