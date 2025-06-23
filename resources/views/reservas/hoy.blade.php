@extends('layouts.app')

@section('title', 'Reservas de Hoy')

@push('styles')
    <style>
        :root {
            --background-color: #1a1a1a;
            --surface-color: #242424;
            --primary-color: #00aaff;
            --text-color: #e0e0e0;
            --text-muted-color: #888;
            --border-color: #333;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
        }

        .main-content {
            padding: 2rem;
        }

        h1 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 2rem;
        }


        .alert-custom {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
            border-radius: 4px;
        }

        .alert-custom.alert-success {
            background-color: rgba(0, 170, 255, 0.1);
            border-left-color: var(--primary-color);
            color: var(--text-color);
        }

        .alert-custom.alert-danger {
            background-color: rgba(255, 59, 48, 0.1);
            border-left-color: #ff3b30;
            color: var(--text-color);
        }

        .button,
        .button-link {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .button:hover,
        .button-link:hover {
            background-color: #0088cc;
            transform: translateY(-2px);
        }

        .button-secondary {
            background-color: var(--surface-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .button-secondary:hover {
            background-color: var(--border-color);
        }

        .search-form {
            margin: 2rem 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .search-form input[type="text"] {
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background-color: var(--surface-color);
            color: var(--text-color);
            flex-grow: 1;
            font-size: 1rem;
        }

        .search-form input[type="text"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px #00aaff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
            font-size: 0.9em;
            background-color: var(--surface-color);
            border-radius: 8px;
            overflow: hidden;
        }

        th,
        td {
            padding: 1rem;
            text-align: left;
            vertical-align: middle;
        }

        thead {
            border-bottom: 2px solid var(--border-color);
        }

        th {
            color: var(--text-muted-color);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid var(--border-color);
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody tr:hover {
            background-color: var(--border-color);
        }

        .actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .actions a,
        .eliminar-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .actions a:hover,
        .eliminar-link:hover {
            color: #0088cc;
        }

        .form-eliminar {
            display: none;
            align-items: center;
            gap: 0.5rem;
        }

        .form-eliminar span {
            color: var(--text-muted-color);
        }

        .form-eliminar .button {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
        }

        .button-danger {
            background-color: #ff3b30;
        }

        .button-danger:hover {
            background-color: #c7001e;
        }

        .search-form select {
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background-color: var(--surface-color);
            color: var(--text-color);
            font-size: 1rem;
        }

        .search-form select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px #00aaff;
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <div class="table-container">
            <div class="table-header">
                <h2>Reservas para Hoy ({{ \Carbon\Carbon::now()->format('d/m/Y') }})</h2>
                <a href="{{ route('admin.reservas.opciones') }}" class="button" style="background-color: #6c757d;">Volver a
                    Opciones</a>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Cancha</th>
                            <th>Cliente</th>
                            <th>Estado Actual</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reservasHoy as $reserva)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($reserva->hora_inicio)->format('H:i') }} -
                                    {{ \Carbon\Carbon::parse($reserva->hora_fin)->format('H:i') }}</td>
                                <td>{{ $reserva->cancha->nombre ?? 'N/A' }}</td>
                                <td>{{ $reserva->cliente->nombre ?? 'N/A' }}</td>
                                <td>
                                    <span
                                        class="status status-{{ str_replace(' ', '-', strtolower($reserva->estado)) }}">{{ $reserva->estado }}</span>
                                </td>
                                <td class="actions">
                                    {{-- ENLACE CORREGIDO --}}
                                    <a href="{{ route('reservas.asistencia.form', $reserva) }}" class="btn-edit">Marcar
                                        Asistencia</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">No hay reservas programadas para hoy.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
