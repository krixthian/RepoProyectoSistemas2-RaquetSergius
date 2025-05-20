{{-- resources/views/dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard</title>

  <!-- Bootstrap CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />

  <!-- ApexCharts -->
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

  <style>
    body { background: #f8f9fa; }
    .card { box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
  </style>
</head>
<body>
  <div class="container py-4">
    <h1 class="mb-4">Dashboard Completo</h1>

    <div class="row gy-4">
      {{-- Gráfico de prueba --}}
      <div class="col-12">
        <div class="card">
          <div class="card-header">Prueba Rápida</div>
          <div class="card-body">
            <div id="tinyChart" style="height: 200px;"></div>
          </div>
        </div>
      </div>

      {{-- Top 5 Clientes --}}
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">Top 5 Clientes</div>
          <div class="card-body">
            <div id="chartClientes" style="height: 300px;"></div>
          </div>
        </div>
      </div>

      {{-- Reservas por Fecha --}}
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">Reservas por Fecha</div>
          <div class="card-body">
            <div id="chartReservas" style="height: 300px;"></div>
          </div>
        </div>
      </div>

      {{-- Estado de Reservas --}}
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">Estado de Reservas</div>
          <div class="card-body">
            <div id="chartEstados" style="height: 300px;"></div>
          </div>
        </div>
      </div>

      {{-- Top 5 Canchas --}}
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

  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      console.log("Dashboard cargado — ApexCharts:", typeof ApexCharts);

      // Gráfico de prueba mínimo
      const tinyEl = document.querySelector("#tinyChart");
      new ApexCharts(tinyEl, {
        chart: { type: 'bar', toolbar: { show: false } },
        series: [{ data: [1, 2, 3] }],
        xaxis: { categories: ['X','Y','Z'] }
      }).render();

      // Datos de ejemplo
      const topClientesLabels = ['Cliente A','Cliente B','Cliente C','Cliente D','Cliente E'];
      const topClientesData   = [12,9,7,5,3];
      const reservasLabels    = ['2025-01-01','2025-01-02','2025-01-03','2025-01-04','2025-01-05'];
      const reservasData      = [5,8,6,10,4];
      const estadosLabels     = ['confirmada','cancelada','pendiente'];
      const estadosData       = [15,4,6];
      const canchasLabels     = ['Fútbol','Tenis','Padel','Basket','Vóley'];
      const canchasData       = [20,15,10,8,5];

      const commonOptions = {
        chart:      { toolbar: { show: false }, zoom: { enabled: false } },
        grid:       { padding: { left: 10, right: 10 } },
        responsive: [{ breakpoint: 600, options: { chart: { height: 250 } } }]
      };

      // 1) Top Clientes (barra horizontal)
      const clientesEl = document.querySelector("#chartClientes");
      new ApexCharts(clientesEl, {
        ...commonOptions,
        chart:       { type: 'bar', height: '100%' },
        series:      [{ data: topClientesData }],
        xaxis:       { categories: topClientesLabels },
        plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
        colors:      ['#1E90FF']
      }).render();

      // 2) Reservas por Fecha (línea)
      const reservasEl = document.querySelector("#chartReservas");
      new ApexCharts(reservasEl, {
        ...commonOptions,
        chart:  { type: 'line', height: '100%' },
        series: [{ name: 'Reservas', data: reservasData }],
        xaxis:  { categories: reservasLabels },
        stroke: { width: 2, curve: 'smooth' },
        colors: ['#28A745']
      }).render();

      // 3) Estado de Reservas (donut)
      const estadosEl = document.querySelector("#chartEstados");
      new ApexCharts(estadosEl, {
        ...commonOptions,
        chart:       { type: 'donut', height: '100%' },
        series:      estadosData,
        labels:      estadosLabels,
        plotOptions: { pie: { donut: { size: '65%' } } },
        colors:      ['#17A2B8','#FFC107','#DC3545']
      }).render();

      // 4) Top Canchas (barra vertical)
      const canchasEl = document.querySelector("#chartCanchas");
      new ApexCharts(canchasEl, {
        ...commonOptions,
        chart:       { type: 'bar', height: '100%' },
        series:      [{ data: canchasData }],
        xaxis:       { categories: canchasLabels },
        plotOptions: { bar: { borderRadius: 4 } },
        colors:      ['#6F42C1']
      }).render();
    });
  </script>
</body>
</html>
