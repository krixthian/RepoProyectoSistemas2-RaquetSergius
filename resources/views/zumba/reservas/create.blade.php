@extends('layouts.app')

@section('title', 'Crear Nueva Reserva')

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
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            padding: 2rem;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        h1 {
            color: var(--primary-color);
        }

        .form-card {
            background-color: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            margin-top: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        @media (min-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 500;
            color: var(--text-muted-color);
        }

        input,
        select {
            width: 100%;
            padding: 0.75rem;
            box-sizing: border-box;
            border: 1px solid var(--border-color);
            background-color: var(--background-color);
            color: var(--text-color);
            border-radius: 8px;
            font-size: 1rem;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 170, 255, 0.3);
        }

        .form-actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
        }

        .button {
            display: inline-block;
            padding: 0.75rem 1.5rem;
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

        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
            border-radius: 4px;
        }

        .alert-danger {
            background-color: rgba(255, 59, 48, 0.1);
            border-left-color: var(--danger-color);
            color: var(--text-color);
        }

        .alert-danger ul {
            margin: 0;
            padding-left: 1.5rem;
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <h1>Crear Nueva Inscripción de Zumba</h1>

        <div class="form-card">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>¡Ups! Hubo algunos problemas.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- CAMBIO: La ruta ahora apunta a zumba.reservas.store --}}
            <form action="{{ route('zumba.reservas.store') }}" method="POST">
                @csrf
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="cliente_id">Cliente</label>
                        <select id="cliente_id" name="cliente_id" required>
                            <option value="">-- Seleccione un cliente --</option>
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->cliente_id }}" {{ old('cliente_id') == $cliente->cliente_id ? 'selected' : '' }}>
                                    {{ $cliente->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label for="clase_id">Clase de Zumba</label>
                        <select id="clase_id" name="clase_id" required>
                            <option value="">-- Seleccione una clase --</option>
                            @foreach ($clases as $clase)
                                <option value="{{ $clase->clase_id }}" {{ old('clase_id') == $clase->clase_id ? 'selected' : '' }}>
                                    {{ $clase->diasemama }} ({{ $clase->hora_inicio->format('h:i A') }}) - Instr:
                                    {{ $clase->instructor->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fecha_clase">Fecha de la Clase</label>
                        <input type="date" id="fecha_clase" name="fecha_clase" value="{{ old('fecha_clase') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="monto_pagado">Monto Pagado (Bs.)</label>
                        <input type="number" step="0.01" id="monto_pagado" name="monto_pagado"
                            value="{{ old('monto_pagado') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="metodo_pago">Método de Pago</label>
                        <select id="metodo_pago" name="metodo_pago" required>
                            <option value="">-- Seleccione un método --</option>
                            <option value="Efectivo" {{ old('metodo_pago') == 'Efectivo' ? 'selected' : '' }}>Efectivo
                            </option>
                            <option value="Tarjeta" {{ old('metodo_pago') == 'Tarjeta' ? 'selected' : '' }}>Tarjeta</option>
                            <option value="Transferencia" {{ old('metodo_pago') == 'Transferencia' ? 'selected' : '' }}>
                                Transferencia</option>
                            <option value="QR" {{ old('metodo_pago') == 'QR' ? 'selected' : '' }}>QR</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="button">Crear Reserva</button>
                </div>
            </form>
        </div>
    </div>
@endsection