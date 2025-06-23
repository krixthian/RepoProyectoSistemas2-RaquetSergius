@extends('layouts.app')

@section('title', 'Detalles del Torneo')

@push('styles')
    <style>
        .torneo-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .details-card {
            background-color: var(--surface-color);
            padding: 2rem;
            border-radius: 12px;
        }

        @media (max-width: 992px) {
            .torneo-grid {
                grid-template-columns: 1fr;
            }
        }

        .equipo-lista li,
        .partido-lista li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <div class="welcome-message">
            <h1>{{ $torneo->nombre }}</h1>
            <p>Estado: <span
                    class="status status-{{ str_replace(' ', '-', strtolower($torneo->estado)) }}">{{ $torneo->estado }}</span>
            </p>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="torneo-grid">
            <div class="details-card">
                <h3>Equipos Inscritos ({{ $torneo->equipos->count() }} / {{ $torneo->num_equipos }})</h3>

                @if ($torneo->estado == 'Abierto')
                    <form action="{{ route('torneos.addEquipo', $torneo) }}" method="POST" class="filter-form my-4">
                        @csrf
                        <select name="equipo_id" required>
                            <option value="">Seleccionar equipo para añadir...</option>
                            @foreach ($equiposDisponibles as $equipo)
                                <option value="{{ $equipo->equipo_id }}">{{ $equipo->nombre }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="button">Añadir Equipo</button>
                    </form>
                @else
                    <p class="text-muted my-4">La inscripción está cerrada.</p>
                @endif

                <ul class="equipo-lista list-unstyled">
                    @forelse($torneo->equipos as $equipo)
                        <li>{{ $equipo->nombre }}</li>
                    @empty
                        <li>No hay equipos inscritos todavía.</li>
                    @endforelse
                </ul>
            </div>

            <div class="details-card">
                <h3>Partidos del Torneo</h3>

                @if ($torneo->partidos->isEmpty())
                    @if ($torneo->estado == 'Abierto')
                        <form action="{{ route('torneos.generarPartidos', $torneo) }}" method="POST">
                            @csrf
                            <button type="submit" class="button"
                                onclick="return confirm('¿Estás seguro de generar los partidos? Esta acción cerrará las inscripciones y comenzará el torneo.')">Generar
                                Partidos</button>
                        </form>
                    @else
                        <p>Los partidos aún no han sido generados.</p>
                    @endif
                @else
                    <ul class="partido-lista list-unstyled">
                        @foreach ($torneo->partidos as $partido)
                            <li>
                                <span>
                                    {{ $partido->equipoLocal->nombre ?? 'N/A' }}
                                    <strong>vs</strong>
                                    {{ $partido->equipoVisitante->nombre ?? 'N/A' }}
                                </span>
                                @if ($partido->estado == 'Finalizado')
                                    <span class="status status-active">{{ $partido->resultado_local ?? 0 }} -
                                        {{ $partido->resultado_visitante ?? 0 }}</span>
                                @else
                                    <span class="status status-pending">{{ $partido->estado }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="mt-4">
            <a href="{{ route('torneos.index') }}" class="button" style="background-color: #6c757d;">Volver a Torneos</a>
        </div>
    </div>
@endsection
