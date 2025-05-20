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

        .option-card {
            background-color: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            text-decoration: none;
            /* Para que toda la tarjeta sea clickeable si se envuelve en <a> */
            color: inherit;
            /* Para que el texto dentro de <a> no sea azul por defecto */
            display: block;
            /* Para que <a> ocupe todo el espacio de la tarjeta */
        }

        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .option-card h2 {
            font-size: 1.5em;
            color: #2C3844;
            /* Tomado de tu style.css */
            margin-bottom: 10px;
        }

        .option-card p {
            font-size: 0.95em;
            color: #4A5568;
            margin-bottom: 15px;
            min-height: 40px;
            /* Para alinear tarjetas con descripciones de diferente longitud */
        }

        .option-card .status {
            font-size: 0.8em;
            font-weight: bold;
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
            color: #E2E8F0;
            cursor: not-allowed;
        }
    </style>
@endpush

@section('content')
    <div class="admin-panel-container">
        <div class="welcome-message">
            <h1>Bienvenido al Panel de Administración, {{ Auth::user()->nombre }}</h1>
            <p>Selecciona una opción para comenzar a gestionar el sistema.</p>
        </div>

        <div class="management-options">

            <a href="{{ route('reservas.index') }}" class="option-card">
                <h2>Gestión de Reservas</h2>
                <p>Administra las reservas de canchas y otros servicios.</p>
                <span class="status status-implemented">Implementado</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Gestionar</span>
                </div>
            </a>




            <a href="{{ route('admin.empleados.index') }}" class="option-card">
                <h2>Gestión de Empleados</h2>
                <p>Administra los usuarios empleados del sistema (roles, accesos, etc.).</p>
                <span class="status status-pending">Listado Implementado (CRUD Pendiente)</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Ver Lista</span>
                </div>
            </a>


            <a href="{{ route('clientes.index') }}" class="option-card">
                <h2>Gestión de Clientes</h2>
                <p>Visualiza y administra la información de los clientes.</p>
                <span class="status ">yasta</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Gestionar</span>
                </div>

            </a>


            <div class="option-card">
                <h2>Gestión de Canchas</h2>
                <p>Administra las canchas disponibles, tipos y precios.</p>
                <span class="status status-pending">Pendiente</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage disabled">Gestionar</span>
                </div>
            </div>


            <div class="option-card">
                <h2>Gestión de Áreas de Zumba</h2>
                <p>Administra las áreas para clases de Zumba, capacidades y horarios.</p>
                <span class="status status-pending">Pendiente</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage disabled">Gestionar</span>
                </div>
            </div>


            <div class="option-card">
                <h2>Gestión de Clases de Zumba</h2>
                <p>Define y administra las clases de Zumba, instructores y cupos.</p>
                <span class="status status-pending">Pendiente</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage disabled">Gestionar</span>
                </div>
            </div>


            <div class="option-card">
                <h2>Gestión de Instructores</h2>
                <p>Administra la información y especialidades de los instructores.</p>
                <span class="status status-pending">Pendiente</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage disabled">Gestionar</span>
                </div>
            </div>


            <div class="option-card">
                <h2>Gestión de Eventos y Torneos</h2>
                <p>Organiza y administra eventos especiales y torneos.</p>
                <span class="status status-pending">Pendiente</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage disabled">Gestionar</span>
                </div>
            </div>


            <div class="option-card">
                <h2>Gestión de Membresías</h2>
                <p>Crea y administra los planes de membresía para clientes.</p>
                <span class="status status-pending">Pendiente</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage disabled">Gestionar</span>
                </div>
            </div>


            <a href="{{ route('dashboard') }}" class="option-card">
                <h2>Reportes y Estadísticas</h2>
                <p>Visualiza datos importantes sobre el funcionamiento del sistema.</p>
                <span class="status status-implemented">Dashboard Implementado</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Ver Dashboard</span>
                </div>
            </a>


        </div>
    </div>
@endsection
