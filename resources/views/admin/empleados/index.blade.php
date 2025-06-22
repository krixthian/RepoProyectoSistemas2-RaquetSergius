@extends('layouts.app')


@section('title', 'Panel de Administración') {{-- Título específico de la página --}}

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

        .btn-manage. {
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

            <a href="{{ route('admin.reservas.opciones') }}" class="option-card">
                <h2>Gestión de Reservas</h2>
                <p>Administra las reservas de canchas y otros servicios.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Gestionar</span>
                </div>
            </a>




            <a href="/empleados" class="option-card">
                <h2>Gestión de Empleados</h2>
                <p>Administra los usuarios empleados del sistema (roles, accesos, etc.).</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Ver Lista</span>
                </div>
            </a>


            <a href="{{ route('clientes.index') }}" class="option-card">
                <h2>Gestión de Clientes</h2>
                <p>Visualiza y administra la información de los clientes.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Gestionar</span>
                </div>

            </a>

            <a href="{{ route('clientes.opciones') }}" class="option-card">
                <h2>Puntos y Premios</h2>
                <p>Gestiona los puntos de fidelidad y el canje de premios para clientes.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Ir a Opciones</span>
                </div>
            </a>

            <div class="option-card">
                <h2>Gestión de Premios</h2>
                <p>Administrar los premios disponibles para canjear.</p>
                <span class="status status-implemented"> ------------------------------------------</span>
                <div style="margin-top: 10px">
                    <a href="{{ route('premios.index') }}" class="btn-manage">
                        Gestionar
                    </a>
                </div>
            </div>



            <div class="option-card">
                <h2>Gestión de Áreas de Zumba</h2>
                <p>Administra las áreas para clases de Zumba, capacidades y horarios.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage ">Gestionar</span>
                </div>
            </div>

            <a href="{{ route('zumba.opciones') }}" class="option-card">
                <h2>Gestión de Clases de Zumba</h2>
                <p>Define y administra las clases de Zumba, instructores y mas.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Gestionar</span>
                </div>
            </a>

            <a href="{{ route('zumba.agendar') }}" class="option-card">
                <h2>Agendar Sesion de Zumba</h2>
                <p>Permite agendar nuevas sesiones de Zumba para los clientes.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Agendar</span>
                </div>
            </a>
            <div class="option-card">
                <h2>Gestión de Instructores</h2>
                <p>Administra la información y especialidades de los instructores.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage ">Gestionar</span>
                </div>
            </div>

            <a href="/torneos" class="option-card">

                <h2>Gestión de Eventos y Torneos</h2>
                <p>Organiza y administra eventos especiales y torneos.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Gestionar</span>
                </div>

            </a>

            <a href="{{ route('dashboard') }}" class="option-card">

                <h2>Reportes y Estadísticas</h2>
                <p>Visualiza datos importantes sobre el funcionamiento del sistema.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Ver Dashboard</span>
                </div>

            </a>


            <a href="{{ route('admin.churn.index') }}" class="option-card">

                <h2>Tasa de abandono (Chrun)</h2>
                <p>Reportes analisis de Churn | tasa de abandono</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Ver</span>
                </div>

            </a>


        </div>
    </div>
@endsection
