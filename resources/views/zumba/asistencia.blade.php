@extends('layouts.app')
@section('title', 'Marcar Asistencia de Zumba')
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
        <div class="welcome-message">
            <h1>Marcar Asistencia a Clase de Zumba</h1>
            <p>Inscripción #{{ $inscripcion->inscripcion_id }}</p>
        </div>

        <div class="details-card" style="background-color: var(--surface-color); padding: 2rem; border-radius: 12px;">
            <h3>Detalles de la Inscripción</h3>
            <div class="details-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem;">
                <div class="detail-item"><strong>Cliente:</strong> <span>{{ $inscripcion->cliente->nombre ?? 'N/A' }}</span>
                </div>
                <div class="detail-item"><strong>Clase:</strong> <span>{{ $inscripcion->claseZumba->dia_semana }} a las
                        {{ \Carbon\Carbon::parse($inscripcion->claseZumba->hora_inicio)->format('H:i') }}</span></div>
                <div class="detail-item"><strong>Instructor:</strong>
                    <span>{{ $inscripcion->claseZumba->instructor->nombre ?? 'N/A' }}</span>
                </div>
                <div class="detail-item"><strong>Área:</strong>
                    <span>{{ $inscripcion->claseZumba->area->nombre ?? 'N/A' }}</span>
                </div>
            </div>

            <div style="margin-top: 2rem;">
                <p>Confirma la asistencia del cliente a esta clase.</p>
                <div class="form-actions" style="margin-top: 1rem; display: flex;">
                    <form action="{{ route('zumba.inscripciones.actualizarEstado', $inscripcion) }}" method="POST">
                        @csrf
                        <input type="hidden" name="estado" value="Asistió">
                        <button type="submit" class="button" style="background-color: #28a745;">Asistió</button>
                    </form>
                    <form action="{{ route('zumba.inscripciones.actualizarEstado', $inscripcion) }}" method="POST"
                        style="margin-left: 1rem;">
                        @csrf
                        <input type="hidden" name="estado" value="No Asistió">
                        <button type="submit" class="button" style="background-color: #dc3545;">No Asistió</button>
                    </form>
                </div>
            </div>
        </div>
        <div style="margin-top: 2rem;">
            <a href="{{ route('zumba.asistencia.hoy') }}" class="button" style="background-color: #6c757d;">Volver a la
                lista</a>
        </div>
    </div>
@endsection
