@extends('layouts.app')


@section('title', 'Panel de RESERVAS') {{-- Título específico de la página --}}

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
    <div class="admin-panel-container">
        <div class="welcome-message">
            <h1 style="color: var(--blueraquet-color);">Bienvenido al Panel de Administración, {{ Auth::user()->nombre }}
            </h1>
            <p>Selecciona una opción para comenzar a gestionar el sistema.</p>
        </div>

        <div class="management-options">

            <a href="{{ route('reservas.index') }}" class="option-card">
                <h2>Administrar Reservas</h2>
                <p>Administra las reservas y sus datos.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Gestionar</span>
                </div>
            </a>




            <a href="{{ route('admin.reservas.pendientes') }}" class="option-card">
                <h2>Reservas Pendientes</h2>
                <p>Confirmar y Rechazar Reservas</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Ver Lista</span>
                </div>
            </a>



            <a href="{{ route('canchas.disponibilidad') }}" class="option-card">
                <h2>Disponibilidad de Canchas</h2>
                <p>Ver calendario de canchas disponibles</p>
                <span class="status status-implemented">----------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Gestionar</span>
                </div>
            </a>


            <a href="{{ route('reservas.hoy') }}" class="option-card">
                <h2>Asistencia de Hoy</h2>
                <p>Revisa las reservas del día y marca la asistencia de los clientes.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Ver Hoy</span>
                </div>
            </a>





        </div>
    </div>
@endsection
