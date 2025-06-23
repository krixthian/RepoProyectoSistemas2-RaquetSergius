@extends('layouts.app')

@section('title', 'Marcar Asistencia de Reserva')

@push('styles')
    <style>
        .details-card {
            background-color: var(--surface-color);
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .details-card h3 {
            margin-top: 0;
            color: var(--blueraquet-color);
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .detail-item strong {
            display: block;
            color: var(--text-muted-color);
            margin-bottom: 0.25rem;
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <div class="welcome-message">
            <h1>Marcar Asistencia</h1>
            <p>Reserva #{{ $reserva->reserva_id }}</p>
        </div>

        <div class="details-card">
            <h3>Detalles de la Reserva</h3>
            <div class="details-grid">
                <div class="detail-item">
                    <strong>Cliente:</strong>
                    <span>{{ $reserva->cliente->nombre ?? 'N/A' }}</span>
                </div>
                <div class="detail-item">
                    <strong>Cancha:</strong>
                    <span>{{ $reserva->cancha->nombre ?? 'N/A' }}</span>
                </div>
                <div class="detail-item">
                    <strong>Fecha:</strong>
                    <span>{{ \Carbon\Carbon::parse($reserva->fecha)->format('d/m/Y') }}</span>
                </div>
                <div class="detail-item">
                    <strong>Hora:</strong>
                    <span>{{ \Carbon\Carbon::parse($reserva->hora_inicio)->format('H:i') }} -
                        {{ \Carbon\Carbon::parse($reserva->hora_fin)->format('H:i') }}</span>
                </div>
            </div>

            <div style="margin-top: 2rem;">
                <p>Confirma la asistencia del cliente para esta reserva.</p>
                <div class="form-actions" style="margin-top: 1rem; display: flex;">
                    {{-- Botón para marcar como ASISTIÓ --}}
                    <form action="{{ route('reservas.actualizarEstado', $reserva->reserva_id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="estado" value="Completada">
                        <button type="submit" class="button" style="background-color: #28a745;">Asistió
                            (Completada)</button>
                    </form>

                    {{-- Botón para marcar como NO ASISTIÓ --}}
                    <form action="{{ route('reservas.actualizarEstado', $reserva->reserva_id) }}" method="POST"
                        style="margin-left: 1rem;">
                        @csrf
                        <input type="hidden" name="estado" value="No asistio">
                        <button type="submit" class="button" style="background-color: #dc3545;">No Asistió</button>
                    </form>
                </div>
            </div>
        </div>

        <div style="margin-top: 2rem;">
            <a href="{{ route('reservas.hoy') }}" class="button button-secondary" style="background-color: #6c757d;">Volver
                a la lista de Hoy</a>
        </div>
    </div>
@endsection
