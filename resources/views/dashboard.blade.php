{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard | Raquet Sergius')

@push('styles')
<style>
    .card { 
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
    }
    .summary-card { 
        border-left: 4px solid #4e73df; 
        height: 100%;
    }
    .chart-container {
        height: 200px;
    }
</style>
@endpush

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Dashboard Completo</h1>

    <!-- Sección de resumen -->
    <div class="row mb-4">
        <!-- Empleados por rol -->
        <div class="col-md-4 mb-3">
            <div class="card summary-card h-100">
                <div class="card-body">
                    <h5 class="card-title">Empleados</h5>
                    <div id="chartEmpleados" class="chart-container"></div>
                </div>
            </div>
        </div>

        <!-- Registro de clientes -->
        <div class="col-md-4 mb-3">
            <div class="card summary-card h-100">
                <div class="card-body">
                    <h5 class="card-title">Registro de Clientes (por semana)</h5>
                    <div id="chartClientesSemanales" class="chart-container"></div>
                </div>
            </div>
        </div>

        <!-- Estado de clientes -->
        <div class="col-md-4 mb-3">
            <div class="card summary-card h-100">
                <div class="card-body">
                    <h5 class="card-title">Estado de Clientes</h5>
                    <div id="chartEstadoClientes" class="chart-container"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row gy-4">
        <!-- Top 5 Clientes -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Top 5 Clientes</div>
                <div class="card-body">
                    <div id="chartClientes" style="height: 300px;"></div>
                </div>
            </div>
        </div>

        <!-- Reservas por Fecha -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Reservas por Fecha</div>
                <div class="card-body">
                    <div id="chartReservas" style="height: 300px;"></div>
                </div>
            </div>
        </div>

        <!-- Estado de Reservas -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Estado de Reservas</div>
                <div class="card-body">
                    <div id="chartEstados" style="height: 300px;"></div>
                </div>
            </div>
        </div>

        <!-- Top 5 Canchas -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Top 5 Canchas</div>
                <div class="card-body">
                    <div id="chartCanchas" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    // Gráfico de empleados por rol
    new ApexCharts(document.querySelector("#chartEmpleados"), {
        chart: { 
            type: 'donut',
            height: '100%' 
        },
        series: @json($empleadosData),
        labels: @json($empleadosLabels),
        legend: { position: 'bottom' }
    }).render();

    // Gráfico de clientes por semana
    new ApexCharts(document.querySelector("#chartClientesSemanales"), {
        chart: { 
            type: 'bar',
            height: '100%' 
        },
        series: [{ 
            name: 'Clientes', 
            data: @json($semanasData) 
        }],
        xaxis: { 
            categories: @json($semanasLabels),
            labels: {
                show: true,
                rotate: -45,
                style: { fontSize: '10px' }
            }
        },
        plotOptions: { 
            bar: { 
                borderRadius: 4,
                horizontal: false
            } 
        }
    }).render();

    // Gráfico de estado de clientes
    const totalClientes = @json(array_sum($clientesEstadoData)); 
    const activos = @json($clientesEstadoData[0] ?? 0);
    const porcentajeActivos = totalClientes > 0 ? Math.round((activos / totalClientes) * 100) : 0;

    new ApexCharts(document.querySelector("#chartEstadoClientes"), {
        chart: { 
            type: 'radialBar',
            height: '100%' 
        },
        series: [porcentajeActivos],
        labels: ['Activos'],
        plotOptions: {
            radialBar: {
                hollow: { size: '70%' },
                dataLabels: {
                    name: { 
                        fontSize: '16px',
                        show: true
                    },
                    value: { 
                        fontSize: '24px',
                        show: true,
                        formatter: function(val) {
                            return val.toFixed(0) + '%';
                        }
                    }
                }
            }
        }
    }).render();

    // Gráfico Top 5 Clientes
    new ApexCharts(document.querySelector("#chartClientes"), {
        chart: { 
            type: 'bar',
            height: '100%' 
        },
        series: [{ 
            name: 'Reservas', 
            data: @json($topClientesData) 
        }],
        xaxis: { 
            categories: @json($topClientesLabels),
            labels: { style: { fontSize: '12px' } }
        },
        plotOptions: { 
            bar: { 
                borderRadius: 4,
                horizontal: true
            } 
        }
    }).render();

    // Gráfico Reservas por Fecha
    new ApexCharts(document.querySelector("#chartReservas"), {
        chart: { 
            type: 'line',
            height: '100%' 
        },
        series: [{ 
            name: 'Reservas', 
            data: @json($reservasFechaData) 
        }],
        xaxis: { 
            categories: @json($reservasFechaLabels),
            type: 'datetime',
            labels: {
                datetimeFormatter: {
                    year: 'yyyy',
                    month: 'MMM \'yy',
                    day: 'dd MMM',
                    hour: 'HH:mm'
                }
            }
        },
        tooltip: { x: { format: 'dd MMM yyyy' } }
    }).render();

    // Gráfico Estados de Reservas
    new ApexCharts(document.querySelector("#chartEstados"), {
        chart: { 
            type: 'pie',
            height: '100%' 
        },
        series: @json($estadosData),
        labels: @json($estadosLabels),
        legend: { position: 'bottom' }
    }).render();

    // Gráfico Top 5 Canchas
    new ApexCharts(document.querySelector("#chartCanchas"), {
        chart: { 
            type: 'bar',
            height: '100%' 
        },
        series: [{ 
            name: 'Reservas', 
            data: @json($usoCanchasData) 
        }],
        xaxis: { 
            categories: @json($usoCanchasLabels),
            labels: { style: { fontSize: '12px' } }
        },
        plotOptions: { 
            bar: { 
                borderRadius: 4,
                horizontal: true
            } 
        }
    }).render();

</script>
@endpush
