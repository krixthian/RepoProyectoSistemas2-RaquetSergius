@extends('layouts.app')

@section('title', 'Reportes y Estadísticas')

@push('styles')
    <style>
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .report-card {
            background-color: var(--text-color);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .report-card h3 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            color: var(--blueraquet-color);
        }

        .report-card .stat {
            font-size: 2rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .report-card .sub-stat {
            font-size: 0.9rem;
            color: var(--text-muted-color);
        }

        .filter-card {
            background-color: var(--surface-color);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            align-items: flex-end;
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <div class="welcome-message">
            <h1 style="color: var(--blueraquet-color);">Reportes y Estadísticas</h1>
            <p>Análisis financiero y de demanda para Wally y Zumba.</p>
        </div>

        <div class="filter-card">
            <form action="{{ route('dashboard') }}" method="GET" class="filter-form">
                <div>
                    <label for="fecha_inicio">Fecha de Inicio</label>
                    <input type="date" name="fecha_inicio" value="{{ $fechaInicio }}" class="form-control">
                </div>
                <div>
                    <label for="fecha_fin">Fecha de Fin</label>
                    <input type="date" name="fecha_fin" value="{{ $fechaFin }}" class="form-control">
                </div>
                <button type="submit" class="button">Filtrar</button>
            </form>
        </div>

        <div class="report-grid mb-4">
            <div class="report-card text-center">
                <h3>Ingresos Totales</h3>
                <p class="stat">Bs. {{ number_format($ingresosTotales, 2) }}</p>
            </div>
            <div class="report-card text-center">
                <h3>Ingresos por Wally</h3>
                <p class="stat">Bs. {{ number_format($ingresosReservas, 2) }}</p>
                <p class="sub-stat">{{ $totalReservas }} reservas pagadas</p>
            </div>
            <div class="report-card text-center">
                <h3>Ingresos por Zumba</h3>
                <p class="stat">Bs. {{ number_format($ingresosZumba, 2) }}</p>
                <p class="sub-stat">{{ $totalInscripciones }} inscripciones pagadas</p>
            </div>
        </div>

        <div class="report-grid">
            <div class="report-card" style="grid-column: 1 / -1;">
                <h3>Ingresos Diarios (Bs.)</h3>
                <canvas id="ingresosDiariosChart"></canvas>
            </div>
            <div class="report-card">
                <h3>Demanda de Canchas por Hora</h3>
                <canvas id="demandaHoraChart"></canvas>
            </div>
            <div class="report-card">
                <h3>Reservas por Cancha</h3>
                <canvas id="reservasCanchaChart"></canvas>
            </div>
            <div class="report-card">
                <h3>Inscripciones por Clase de Zumba</h3>
                <canvas id="demandaZumbaChart"></canvas>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    {{-- Incluir Chart.js desde CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartOptions = {
                plugins: {
                    legend: {
                        labels: {
                            color: 'var(--text-color)'
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            color: 'var(--text-muted-color)'
                        },
                        grid: {
                            color: 'var(--border-color)'
                        }
                    },
                    x: {
                        ticks: {
                            color: 'var(--text-muted-color)'
                        },
                        grid: {
                            color: 'var(--border-color)'
                        }
                    }
                }
            };

            // 1. Gráfico de Ingresos Diarios
            new Chart(document.getElementById('ingresosDiariosChart'), {
                type: 'line',
                data: {
                    labels: @json($ingresosDiariosData->keys()),
                    datasets: [{
                        label: 'Ingresos Totales (Bs)',
                        data: @json($ingresosDiariosData->values()),
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.2)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: chartOptions
            });

            // 2. Gráfico de Demanda por Hora
            new Chart(document.getElementById('demandaHoraChart'), {
                type: 'bar',
                data: {
                    labels: [...Array(24).keys()].map(h => `${h}:00`),
                    datasets: [{
                        label: 'Nº de Reservas',
                        data: @json(array_values($horasDelDia)),
                        backgroundColor: '#2ecc71'
                    }]
                },
                options: chartOptions
            });

            // 3. Gráfico de Reservas por Cancha
            new Chart(document.getElementById('reservasCanchaChart'), {
                type: 'doughnut',
                data: {
                    labels: @json($reservasPorCancha->pluck('nombre')),
                    datasets: [{
                        label: 'Reservas',
                        data: @json($reservasPorCancha->pluck('reservas_count')),
                        backgroundColor: ['#e74c3c', '#f1c40f', '#9b59b6', '#1abc9c', '#34495e']
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: 'var(--text-color)'
                            }
                        }
                    }
                }
            });
            new Chart(document.getElementById('demandaZumbaChart'), {
                type: 'bar',
                data: {
                    labels: @json($inscripcionesPorClase->map(fn($clase) => $clase->dia_semana . ' ' . \Carbon\Carbon::parse($clase->hora_inicio)->format('H:i'))),
                    datasets: [{
                        label: 'Nº de Inscripciones',
                        data: @json($inscripcionesPorClase->pluck('inscripciones_count')),
                        backgroundColor: '#e67e22'
                    }]
                },
                options: chartOptions
            });
        });
    </script>
@endpush
