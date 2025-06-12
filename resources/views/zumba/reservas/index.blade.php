@extends('layouts.app')

@section('title', 'Administrar Reservas de Zumba')

@push('styles')
<style>
    :root {
        --background-color: #1a1a1a; --surface-color: #242424; --primary-color: #00aaff;
        --text-color: #e0e0e0; --text-muted-color: #888; --border-color: #333; --danger-color: #ff3b30;
        --success-color: #30d158; --warning-color: #ff9f0a; --completed-color: #5e5ce6;
    }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background-color: var(--background-color); color: var(--text-color); padding: 2rem; }
    .container { max-width: 1400px; margin: 0 auto; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
    h1 { color: var(--primary-color); }
    .button { display: inline-block; padding: 0.65rem 1.25rem; background-color: var(--primary-color); color: #fff; border: none; border-radius: 8px; text-decoration: none; font-weight: 500; cursor: pointer; transition: all 0.2s ease; }
    .button:hover { background-color: #0088cc; transform: translateY(-2px); }
    .table-container { background-color: var(--surface-color); border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem 2rem 2rem 2rem; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th { color: var(--text-muted-color); font-weight: 600; padding: 1rem; border-bottom: 2px solid var(--border-color); white-space: nowrap; }
    td { padding: 1rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
    tbody tr:last-child td { border-bottom: none; }
    .empty-message td { text-align: center; padding: 3rem; color: var(--text-muted-color); }
    .alert-success { padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; background-color: rgba(48, 209, 88, 0.2); color: var(--success-color); border: 1px solid var(--success-color);}
    .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; font-weight: 500; text-transform: capitalize; }
    .status-Confirmada { background-color: rgba(48, 209, 88, 0.2); color: var(--success-color); }
    .status-Pendiente { background-color: rgba(255, 159, 10, 0.2); color: var(--warning-color); }
    .status-Cancelada { background-color: rgba(255, 59, 48, 0.2); color: var(--danger-color); }
    .status-Completada { background-color: rgba(94, 92, 230, 0.2); color: var(--completed-color); }
    .status-default { background-color: rgba(142, 142, 147, 0.2); color: #8e8e93; }
</style>
@endpush

@section('content')
<div class="container">
    <div class="page-header">
        <h1>Administrar Reservas de Zumba</h1>
        {{-- CAMBIO: La ruta ahora apunta a zumba.reservas.create --}}
        <a href="{{ route('zumba.reservas.create') }}" class="button">Crear Nueva Reserva</a>
    </div>

    {{-- Bloque para mostrar el mensaje de éxito --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="table-container">
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
                @forelse ($reservas as $reserva)
                    <tr>
                        <td>{{ $reserva->cliente->nombre ?? 'Cliente no encontrado' }}</td>
                        <td>
                            <strong>{{ $reserva->claseZumba->diasemama ?? 'Día no definido' }}</strong>
                            <small style="color: var(--text-muted-color); display: block;">
                                {{ $reserva->claseZumba->hora_inicio ? $reserva->claseZumba->hora_inicio->format('h:i A') : '' }} - 
                                {{ $reserva->claseZumba->hora_fin ? $reserva->claseZumba->hora_fin->format('h:i A') : '' }}
                                <br>
                                Instr: {{ $reserva->claseZumba->instructor->nombre ?? 'N/A' }}
                                | Salón: {{ $reserva->claseZumba->area->nombre ?? 'N/A' }}
                            </small>
                        </td>
                        <td>{{ $reserva->fecha_clase ? \Carbon\Carbon::parse($reserva->fecha_clase)->format('d/m/Y') : 'No definida' }}</td>
                        <td style="white-space: nowrap;">{{ number_format($reserva->monto_pagado, 2) }} Bs.</td>
                        <td>{{ $reserva->fecha_inscripcion ? $reserva->fecha_inscripcion->format('d/m/Y h:i A') : 'N/A' }}</td>
                        <td>
                            <span class="status-badge status-{{ $reserva->estado ?? 'default' }}">
                                {{ $reserva->estado ?? 'Indefinido' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr class="empty-message">
                        <td colspan="6">Aún no hay ninguna reserva registrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection