@extends('layouts.app')


@section('title', 'Panel de opciones zumba') {{-- Título específico de la página --}}

@push('styles')
    <style>
        .admin-panel-container {
            padding: 20px;
        }

        .welcome-message h1 {
            font-size: 2em;
            color: #333;
            margin-bottom: 10px;
        }

        .welcome-message p {
            font-size: 1.1em;
            color: #555;
            margin-bottom: 30px;
        }

        .management-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }



        .status-implemented {
            color: #38A169;
            /* Verde */
        }

        .status-pending {
            color: #D69E2E;
            /* Naranja/Amarillo */
        }

        .btn-manage {
            display: inline-block;
            padding: 8px 15px;
            background-color: #2C3844;
            /* Tomado de tu style.css */
            color: #59FFD8;
            /* Tomado de tu style.css */
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }

        .btn-manage:hover {
            background-color: #1a2229;
        }

        .btn-manage.disabled {
            background-color: #A0AEC0;
            z color: #E2E8F0;
            cursor: not-allowed;
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <div class="welcome-message">
            <h1 style="color: var(--blueraquet-color);">Gestión de Zumba</h1>
            <p>Selecciona una opción para administrar clases, instructores, inscripciones y más.</p>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="management-options">

            <a href="{{ route('zumba.reservas.index') }}" class="option-card">
                <h2>Gestión de inscripciones</h2>
                <p>gestionar inscripciones de Zumba.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Gestionar</span>
                </div>
            </a>
            <a href="{{ route('instructores.index') }}" class="option-card">
                <h2>Gestión de Instructores</h2>
                <p>Añade, edita o elimina instructores de Zumba.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Gestionar</span>
                </div>
            </a>

            <a href="{{ route('zumba.pendientes') }}" class="option-card">
                <h2>Revisión de Pagos</h2>
                <p>Aprueba o rechaza los pagos de las inscripciones a clases.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Revisar Pagos</span>
                </div>
            </a>


            <a href="{{ route('zumba.agendar') }}" class="option-card">
                <h2>Gestión de Clases</h2>
                <p>Crea, modifica o cancela las clases de Zumba disponibles.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Agendar</span>
                </div>
            </a>
            <a href="{{ route('zumba.asistencia.hoy') }}" class="option-card">
                <h2>Asistencia de Hoy</h2>
                <p>Revisa las inscripciones del día y marca la asistencia a clases.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Ver Hoy</span>
                </div>
            </a>
            <a href="{{ route('clases-zumba.index') }}" class="option-card">
                <h2>Gestión de Clases</h2>
                <p>Crea, modifica o cancela las clases de Zumba disponibles.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Gestionar</span>
                </div>
            </a>
        </div>
    </div>
@endsection
