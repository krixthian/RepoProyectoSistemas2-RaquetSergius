<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard BETAAAAAAA</title>
  <style>
    /* Reset y estilos generales */
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; background-color: #f4f4f4; }
    .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
    .sidebar { background-color: #333; color: #fff; width: 250px; position: fixed; height: 100%; overflow: auto; }
    .sidebar a { display: block; color: white; padding: 16px; text-decoration: none; }
    .sidebar a:hover { background-color: #575757; }
    .main { margin-left: 260px; padding: 20px; }
    .card { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
    canvas { width: 100%; height: 200px; }
  </style>
  <!-- Chart.js desde CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <div class="header">
    <h1>Dashboard BETAAAAAAA</h1>
  </div>
  <div class="sidebar">
    <a href="#">Reportes</a>
    <a href="#">Cambios</a>
    <a href="#">Configuración</a>
    <a href="#">Salir</a>
  </div>
  <div class="main">
    <div class="grid">
      <!-- Gráfico de Membresías -->
      <div class="card">
        <h2>Membresías Más Usadas</h2>
        <canvas id="membresiasChart"></canvas>
      </div>
      <!-- Gráfico de Reservas por Mes -->
      <div class="card">
        <h2>Reservas por Mes</h2>
        <canvas id="reservasChart"></canvas>
      </div>
      <!-- Gráfico de Estado de Reservas -->
      <div class="card">
        <h2>Estado de Reservas</h2>
        <canvas id="estadosChart"></canvas>
      </div>
    </div>
  </div>

  <script>
    // Pasar las variables PHP a JavaScript
    const membresiasLabels = @json($membresias_labels);
    const membresiasData   = @json($membresias_data);
    const reservasLabels   = @json($reservas_labels);
    const reservasData     = @json($reservas_data);
    const estadosLabels    = @json($estados_labels);
    const estadosData      = @json($estados_data);

    // Gráfico de Membresías (Barras)
    new Chart(document.getElementById('membresiasChart'), {
      type: 'bar',
      data: {
        labels: membresiasLabels,
        datasets: [{
          label: 'Cantidad',
          data: membresiasData,
          backgroundColor: 'rgba(75, 192, 192, 0.6)',
          borderColor: 'rgba(75, 192, 192, 1)',
          borderWidth: 1
        }]
      }
    });

    // Gráfico de Reservas por Mes (Línea)
    new Chart(document.getElementById('reservasChart'), {
      type: 'line',
      data: {
        labels: reservasLabels,
        datasets: [{
          label: 'Reservas',
          data: reservasData,
          backgroundColor: 'rgba(54, 162, 235, 0.6)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 2,
          fill: true
        }]
      }
    });

    // Gráfico de Estado de Reservas (Pie)
    new Chart(document.getElementById('estadosChart'), {
      type: 'pie',
      data: {
        labels: estadosLabels,
        datasets: [{
          label: 'Estado',
          data: estadosData,
          backgroundColor: [
            'rgba(75, 192, 192, 0.6)',
            'rgba(255, 99, 132, 0.6)'
          ]
        }]
      }
    });
  </script>
</body>
</html>
