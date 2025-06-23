@extends('layouts.app')

@section('title', 'Administrar Reservas de Zumba')

@push('styles')
    <style>
        :root {
            --background-color: #1a1a1a;
            --surface-color: #242424;
            --primary-color: #00aaff;
            --text-color: #e0e0e0;
            --text-muted-color: #888;
            --border-color: #333;
            --danger-color: #ff3b30;
            --success-color: #30d158;
            --warning-color: #ff9f0a;
            --completed-color: #5e5ce6;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            padding: 2rem;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        h1 {
            color: var(--primary-color);
        }

        .button {
            display: inline-block;
            padding: 0.65rem 1.25rem;
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .button:hover {
            background-color: #0088cc;
            transform: translateY(-2px);
        }

        .table-container {
            background-color: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem 2rem 2rem 2rem;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            color: var(--text-muted-color);
            font-weight: 600;
            padding: 1rem;
            border-bottom: 2px solid var(--border-color);
            white-space: nowrap;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .empty-message td {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted-color);
        }

        .alert-success {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            background-color: rgba(48, 209, 88, 0.2);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-Confirmada {
            background-color: rgba(48, 209, 88, 0.2);
            color: var(--success-color);
        }

        .status-Pendiente {
            background-color: rgba(255, 159, 10, 0.2);
            color: var(--warning-color);
        }

        .status-Cancelada {
            background-color: rgba(255, 59, 48, 0.2);
            color: var(--danger-color);
        }

        .status-Completada {
            background-color: rgba(94, 92, 230, 0.2);
            color: var(--completed-color);
        }

        .status-default {
            background-color: rgba(142, 142, 147, 0.2);
            color: #8e8e93;
        }

        .filter-card {
            background: var(--surface-color);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>Inscripciones de Zumba</h1>
            <a href="{{ route('zumba.reservas.create') }}" class="button">Crear Nueva Inscripcion</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        {{-- INICIO DEL FORMULARIO DE FILTROS --}}
        <div class="filter-card">
            <form action="{{ route('zumba.reservas.index') }}" method="GET" class="filter-form">
                <div>
                    <label for="cliente_nombre">Nombre del Cliente</label>
                    <input type="text" name="cliente_nombre" id="cliente_nombre" value="{{ request('cliente_nombre') }}"
                        placeholder="Buscar por nombre...">
                </div>
                <div>
                    <label for="fecha_inicio">Fecha de Clase (Desde)</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" value="{{ request('fecha_inicio') }}">
                </div>
                <div>
                    <label for="fecha_fin">Fecha de Clase (Hasta)</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" value="{{ request('fecha_fin') }}">
                </div>
                <div>
                    <label for="estado">Estado</label>
                    <select name="estado" id="estado">
                        <option value="">Todos</option>
                        <option value="Pagado" {{ request('estado') == 'Pagado' ? 'selected' : '' }}>Pagado</option>
                        <option value="Pendiente" {{ request('estado') == 'Pendiente' ? 'selected' : '' }}>Pendiente
                        </option>
                        <option value="Asistió" {{ request('estado') == 'Asistió' ? 'selected' : '' }}>Asistió</option>
                        <option value="No Asistió" {{ request('estado') == 'No Asistió' ? 'selected' : '' }}>No Asistió
                        </option>
                        <option value="Cancelado" {{ request('estado') == 'Cancelado' ? 'selected' : '' }}>Cancelado
                        </option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="button">Filtrar</button>
                    <a href="{{ route('zumba.reservas.index') }}" class="button"
                        style="background-color: #6c757d;">Limpiar</a>
                </div>
            </form>
        </div>
        {{-- FIN DEL FORMULARIO DE FILTROS --}}
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Clase</th>
                        <th>Fecha de la Clase</th>
                        <th>Monto Pagado (Bs.)</th>
                        <th>Fecha de Inscripción</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inscripciones as $inscripcion)
                        <tr>
                            <td>{{ $inscripcion->cliente->nombre ?? 'Cliente no encontrado' }}</td>
                            <td>
                                <strong>{{ $inscripcion->claseZumba->diasemama ?? 'Día no definido' }}</strong>
                                <small style="color: var(--text-muted-color); display: block;">
                                    {{ $inscripcion->claseZumba->hora_inicio ? $inscripcion->claseZumba->hora_inicio->format('h:i A') : '' }}
                                    -
                                    {{ $inscripcion->claseZumba->hora_fin ? $inscripcion->claseZumba->hora_fin->format('h:i A') : '' }}
                                    <br>
                                    Instr: {{ $inscripcion->claseZumba->instructor->nombre ?? 'N/A' }}
                                    | Salón: {{ $inscripcion->claseZumba->area->nombre ?? 'N/A' }}
                                </small>
                            </td>
                            <td>{{ $inscripcion->fecha_clase ? \Carbon\Carbon::parse($inscripcion->fecha_clase)->format('d/m/Y') : 'No definida' }}
                            </td>
                            <td style="white-space: nowrap;">{{ number_format($inscripcion->monto_pagado, 2) }} Bs.</td>
                            <td>{{ $inscripcion->fecha_inscripcion ? $inscripcion->fecha_inscripcion->format('d/m/Y h:i A') : 'N/A' }}
                            </td>
                            <td>
                                <span class="status-badge status-{{ $inscripcion->estado ?? 'default' }}">
                                    {{ $inscripcion->estado ?? 'Indefinido' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">No se encontraron inscripciones con los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
        {{-- Paginación --}}
        <div class="pagination-container" style="margin-top: 20px;">
            {{ $inscripciones->links() }}
        </div>
    </div>
@endsection
