{{-- resources/views/disponibilidad/index.blade.php (Con Tema Oscuro y CSS integrado) --}}

@extends('layouts.app')

@section('content')
<style>
    /* Tus variables de tema oscuro */
    :root {
        --background-color: #1a1a1a;
        --surface-color: #242424;
        --primary-color: #00aaff;
        --text-color: #e0e0e0;
        --text-muted-color: #888;
        --border-color: #333;
        --success-color: #2ecc71; /* Un verde que resalta en temas oscuros */
        --danger-color: #ff3b30;   /* Tu color de peligro */
    }

    /* Estilos para la página de disponibilidad */
    .disponibilidad-container {
        padding: 2rem;
        background-color: var(--background-color);
        font-family: sans-serif;
    }

    .disponibilidad-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .disponibilidad-header h2 {
        margin: 0;
        font-size: 1.75em;
        color: var(--primary-color);
        font-weight: 600;
    }

    /* Estilo para los botones de navegación usando tu clase .button */
    .button {
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

    .button:hover {
        background-color: #0088cc;
        transform: translateY(-2px);
    }

    /* Tabla de disponibilidad adaptada a tu tema */
    .disponibilidad-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 2rem;
        background-color: var(--surface-color);
        border-radius: 8px;
        overflow: hidden; /* Para que los bordes redondeados se apliquen a las celdas */
        table-layout: fixed;
    }

    .disponibilidad-table th,
    .disponibilidad-table td {
        border: 1px solid var(--border-color);
        padding: 0.5rem;
        text-align: center;
        vertical-align: top;
    }

    .disponibilidad-table thead th {
        padding: 1rem;
        color: var(--text-muted-color);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
        background-color: #2c2c2c;
    }
    
    .disponibilidad-table tbody tr:hover {
        background-color: var(--border-color);
    }

    .cancha-nombre {
        font-weight: bold;
        color: var(--primary-color);
        vertical-align: middle !important; /* Centra el nombre de la cancha verticalmente */
        background-color: #2c2c2c;
        padding: 1rem;
    }

    /* Estilos para los slots de hora */
    .slot {
        display: block;
        padding: 8px 4px;
        margin: 4px 0;
        border-radius: 4px;
        font-size: 0.9em;
        font-weight: 500;
        color: #111; /* Texto oscuro para que contraste con los fondos claros */
        text-align: center;
    }
    
    .slot-disponible {
        background-color: var(--success-color);
        color: white;
    }
    
    .slot-ocupado {
        background-color: var(--danger-color);
        color: white;
        cursor: help;
    }

</style>

<div class="disponibilidad-container">
    <div class="disponibilidad-header">
        <a href="{{ route('canchas.disponibilidad', ['date' => $inicioSemana->copy()->subWeek()->toDateString()]) }}" class="button">&lt; Semana Ant.</a>
        
        <h2>
            Disponibilidad: {{ $inicioSemana->format('d/m') }} - {{ $inicioSemana->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)->format('d/m/Y') }}
        </h2>
        
        <a href="{{ route('canchas.disponibilidad', ['date' => $inicioSemana->copy()->addWeek()->toDateString()]) }}" class="button">Semana Sig. &gt;</a>
    </div>

    <div class="table-responsive">
        <table class="disponibilidad-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Cancha</th>
                    @foreach ($diasDeLaSemana as $dia)
                        <th>{{ $dia->translatedFormat('l') }}<br><small>{{ $dia->format('d/m') }}</small></th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($canchas as $cancha)
                    <tr>
                        <td class="cancha-nombre">{{ $cancha->nombre }}</td>
                        @foreach ($diasDeLaSemana as $dia)
                            <td>
                                @foreach ($horarios as $hora)
                                    @php
                                        $fechaKey = $dia->format('Y-m-d');
                                        $horaKey = sprintf('%02d', $hora) . ':00:00';
                                        $reserva = $reservasAgrupadas[$cancha->cancha_id][$fechaKey][$horaKey] ?? null;
                                    @endphp

                                    @if ($reserva)
                                        <div class="slot slot-ocupado" title="Reservado por: {{ $reserva->cliente->nombre ?? 'Cliente no especificado' }} | Estado: {{ $reserva->estado }}">
                                            {{ sprintf('%02d', $hora) }}:00
                                        </div>
                                    @else
                                        <div class="slot slot-disponible">
                                            {{ sprintf('%02d', $hora) }}:00
                                        </div>
                                    @endif
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection