@extends('layouts.app')

@section('title', 'Opciones de Clientes')

@push('styles')
    <style>
        .management-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 2rem;
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <div class="welcome-message">
            <h1 style="color: var(--blueraquet-color);">Opciones de Clientes</h1>
            <p>Selecciona una opción para gestionar los puntos y premios de los clientes.</p>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="management-options">
            <a href="{{ route('puntos.sumar-restar.form') }}" class="option-card">
                <h2>Sumar / Restar Puntos</h2>
                <p>Modifica manualmente los puntos de un cliente por bonificaciones o correcciones.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Gestionar Puntos</span>
                </div>
            </a>

            <a href="{{ route('puntos.canjear.form') }}" class="option-card">
                <h2>Canjear Premios</h2>
                <p>Canjea los puntos de un cliente por premios disponibles en el sistema.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Realizar Canje</span>
                </div>
            </a>

            <a href="{{ route('puntos.canjes.historial') }}" class="option-card">
                <h2>Historial de Canjes</h2>
                <p>Revisa el historial de todos los premios canjeados por los clientes.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Ver Historial</span>
                </div>
            </a>

            <a href="{{ route('puntos.log.historial') }}" class="option-card">
                <h2>Log de Puntos</h2>
                <p>Audita todos los movimientos de puntos, con filtros por cliente y teléfono.</p>
                <span class="status status-implemented">------------------------------------------</span>
                <div style="margin-top: 10px;">
                    <span class="btn-manage">Ver Logs</span>
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


        </div>
    </div>
@endsection
