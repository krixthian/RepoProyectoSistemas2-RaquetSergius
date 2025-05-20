@extends('layouts.app') {{-- O el layout que uses para tu panel --}}

@section('title', 'Análisis de Churn')

@section('content')
    <div class="container-fluid px-4">
        <h1 class="mt-4">Análisis de Tasa de Abandono (Churn Rate)</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li> {{-- Ajusta esta ruta --}}
            <li class="breadcrumb-item active">Análisis de Churn</li>
        </ol>

        {{-- Formulario para seleccionar el periodo --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-calendar-alt me-1"></i> Seleccionar Periodo de
                    Análisis</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.churn.index') }}">
                    <div class="row align-items-end">
                        <div class="col-md-5 col-lg-4 mb-3">
                            <label for="fecha_inicio" class="form-label">Fecha de Inicio:</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                                value="{{ $fecha_inicio_actual ?? '' }}" required>
                        </div>
                        <div class="col-md-5 col-lg-4 mb-3">
                            <label for="fecha_fin" class="form-label">Fecha de Fin:</label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                                value="{{ $fecha_fin_actual ?? '' }}" required>
                        </div>
                        <div class="col-md-2 col-lg-2 mb-3">
                            <button type="submit" class="btn btn-primary w-100">Analizar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if ($resultados)
            {{-- Tarjetas de Resumen --}}
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tasa de Churn
                                        (Periodo)</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $resultados['tasa_de_churn_calculada_total'] }}%</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Clientes en Churn
                                        (Periodo)</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $resultados['clientes_que_hicieron_churn_en_periodo'] }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-slash fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Clientes al
                                        Inicio del Periodo</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $resultados['clientes_al_inicio_periodo'] }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Activos al Final
                                        (Estimado)</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $resultados['clientes_activos_al_final_estimado'] }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-check fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Tabla de Resumen --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-table me-1"></i> Resumen Detallado del
                        Periodo</h6>
                </div>
                <div class="card-body">
                    <h4>{{ $resultados['periodo_analizado'] }}</h4>
                    <p><strong>Definición de Churn:</strong> {{ $resultados['definicion_churn'] }}</p>
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td>Clientes al Inicio del Periodo:</td>
                                <td>{{ $resultados['clientes_al_inicio_periodo'] }}</td>
                            </tr>
                            <tr>
                                <td>Clientes que Hicieron Churn en el Periodo:</td>
                                <td>{{ $resultados['clientes_que_hicieron_churn_en_periodo'] }}</td>
                            </tr>
                            <tr>
                                <td>Clientes Activos Estimados al Final del Periodo:</td>
                                <td>{{ $resultados['clientes_activos_al_final_estimado'] }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Tasa de Churn Calculada (Periodo Total):</td>
                                <td class="fw-bold">{{ $resultados['tasa_de_churn_calculada_total'] }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Graficos --}}
            <div class="row">
                <div class="col-xl-7 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Tendencia Tasa de Churn Mensual (%)</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-area">
                                <canvas id="monthlyChurnRateChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-5 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Distribución de Clientes (Periodo Total)</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-pie pt-4 pb-2">
                                <canvas id="customerDistributionChart"></canvas>
                            </div>
                            <div class="mt-4 text-center small">
                                <span class="mr-2"><i class="fas fa-circle text-success"></i> Al Inicio</span>
                                <span class="mr-2"><i class="fas fa-circle text-danger"></i> Churned</span>
                                <span class="mr-2"><i class="fas fa-circle text-info"></i> Activos al Final</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            @if (request()->has('fecha_inicio'))
                <div class="alert alert-warning" role="alert">
                    No se pudieron generar los resultados para el rango de fechas seleccionado. Asegúrate de que haya datos
                    de clientes y actividad en ese periodo.
                </div>
            @else
                <div class="alert alert-info" role="alert">
                    Selecciona un rango de fechas para calcular la tasa de churn y ver los gráficos.
                </div>
            @endif
        @endif
    </div>
@endsection
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
@push('scripts')
    <script>
        // Esperar a que el DOM esté completamente cargado
        document.addEventListener('DOMContentLoaded', function() {
            // Configuración global de Chart.js para fuentes
            Chart.defaults.global.defaultFontFamily =
                'Nunito, -apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
            Chart.defaults.global.defaultFontColor = '#858796';

            function number_format(number, decimals, dec_point, thousands_sep) {
                number = (number + '').replace(',', '').replace(' ', '');
                var n = !isFinite(+number) ? 0 : +number,
                    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
                    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
                    s = '',
                    toFixedFix = function(n, prec) {
                        var k = Math.pow(10, prec);
                        return '' + Math.round(n * k) / k;
                    };
                s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
                if (s[0].length > 3) {
                    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
                }
                if ((s[1] || '').length < prec) {
                    s[1] = s[1] || '';
                    s[1] += new Array(prec - s[1].length + 1).join('0');
                }
                return s.join(dec);
            }

            @if ($resultados)
                // 1. Gráfico de Distribución de Clientes (Bar Chart)
                var ctxBar = document.getElementById("customerDistributionChart");
                if (ctxBar) { // Asegurarse que el canvas existe
                    var customerDistributionChart = new Chart(ctxBar, {
                        type: 'bar',
                        data: {
                            labels: ["Al Inicio del Periodo", "Churn en Periodo",
                                "Activos al Final (Est.)"
                            ],
                            datasets: [{
                                label: "Número de Clientes",
                                backgroundColor: ["#4e73df", "#e74a3b", "#1cc88a"],
                                hoverBackgroundColor: ["#2e59d9", "#c72a1b", "#0c985a"],
                                borderColor: "#ffffff", // Color del borde de las barras
                                data: [
                                    {{ $resultados['clientes_al_inicio_periodo'] ?? 0 }},
                                    {{ $resultados['clientes_que_hicieron_churn_en_periodo'] ?? 0 }},
                                    {{ $resultados['clientes_activos_al_final_estimado'] ?? 0 }}
                                ],
                            }],
                        },
                        options: {
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    left: 10,
                                    right: 25,
                                    top: 25,
                                    bottom: 0
                                }
                            },
                            scales: {
                                xAxes: [{
                                    gridLines: {
                                        display: false,
                                        drawBorder: false
                                    },
                                    ticks: {
                                        maxTicksLimit: 6
                                    },
                                    maxBarThickness: 50, // Aumentar un poco el grosor
                                }],
                                yAxes: [{
                                    ticks: {
                                        min: 0,
                                        padding: 10,
                                        callback: function(value, index, values) {
                                            return number_format(value);
                                        }
                                    },
                                    gridLines: {
                                        color: "rgb(234, 236, 244)",
                                        zeroLineColor: "rgb(234, 236, 244)",
                                        drawBorder: false,
                                        borderDash: [2],
                                        zeroLineBorderDash: [2]
                                    }
                                }],
                            },
                            legend: {
                                display: false
                            },
                            tooltips: {
                                titleMarginBottom: 10,
                                titleFontColor: '#6e707e',
                                titleFontSize: 14,
                                backgroundColor: "rgb(255,255,255)",
                                bodyFontColor: "#858796",
                                borderColor: '#dddfeb',
                                borderWidth: 1,
                                xPadding: 15,
                                yPadding: 15,
                                displayColors: false,
                                caretPadding: 10,
                                callbacks: {
                                    label: function(tooltipItem, chart) {
                                        var datasetLabel = chart.datasets[tooltipItem.datasetIndex]
                                            .label || '';
                                        return datasetLabel + ': ' + number_format(tooltipItem.yLabel);
                                    }
                                }
                            },
                        }
                    });
                } else {
                    console.error("Canvas con ID 'customerDistributionChart' no encontrado.");
                }
            @endif

            @if ($monthlyChurnData && count($monthlyChurnData) > 0)
                // 2. Gráfico de Tasa de Churn Mensual (Line Chart)
                var ctxLine = document.getElementById("monthlyChurnRateChart");
                if (ctxLine) { // Asegurarse que el canvas existe
                    // Extraer los datos de forma segura
                    let meses = [];
                    let tasasChurn = [];
                    @foreach ($monthlyChurnData as $data)
                        meses.push("{{ $data['mes'] }}");
                        tasasChurn.push({{ $data['tasa_churn'] }});
                    @endforeach

                    var monthlyChurnRateChart = new Chart(ctxLine, {
                        type: 'line',
                        data: {
                            labels: meses,
                            datasets: [{
                                label: "Tasa de Churn (%)",
                                lineTension: 0.3,
                                backgroundColor: "rgba(78, 115, 223, 0.05)",
                                borderColor: "rgba(78, 115, 223, 1)",
                                pointRadius: 3,
                                pointBackgroundColor: "rgba(78, 115, 223, 1)",
                                pointBorderColor: "rgba(78, 115, 223, 1)",
                                pointHoverRadius: 4,
                                pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                                pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                                pointHitRadius: 10,
                                pointBorderWidth: 2,
                                data: tasasChurn,
                            }],
                        },
                        options: {
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    left: 10,
                                    right: 25,
                                    top: 25,
                                    bottom: 0
                                }
                            },
                            scales: {
                                xAxes: [{
                                    // time: { unit: 'month' }, // Solo si tus etiquetas son objetos Date de JS
                                    gridLines: {
                                        display: false,
                                        drawBorder: false
                                    },
                                    ticks: {
                                        maxTicksLimit: 7
                                    }
                                }],
                                yAxes: [{
                                    ticks: {
                                        maxTicksLimit: 5,
                                        padding: 10,
                                        callback: function(value, index, values) {
                                            return number_format(value) + '%';
                                        }
                                    },
                                    gridLines: {
                                        color: "rgb(234, 236, 244)",
                                        zeroLineColor: "rgb(234, 236, 244)",
                                        drawBorder: false,
                                        borderDash: [2],
                                        zeroLineBorderDash: [2]
                                    }
                                }],
                            },
                            legend: {
                                display: false
                            },
                            tooltips: {
                                backgroundColor: "rgb(255,255,255)",
                                bodyFontColor: "#858796",
                                titleMarginBottom: 10,
                                titleFontColor: '#6e707e',
                                titleFontSize: 14,
                                borderColor: '#dddfeb',
                                borderWidth: 1,
                                xPadding: 15,
                                yPadding: 15,
                                displayColors: false,
                                intersect: false,
                                mode: 'index',
                                caretPadding: 10,
                                callbacks: {
                                    label: function(tooltipItem, chart) {
                                        var datasetLabel = chart.datasets[tooltipItem.datasetIndex]
                                            .label || '';
                                        return datasetLabel + ': ' + number_format(tooltipItem.yLabel,
                                            2) + '%';
                                    }
                                }
                            }
                        }
                    });
                } else {
                    console.error("Canvas con ID 'monthlyChurnRateChart' no encontrado.");
                }
            @endif
        });
    </script>
@endpush
