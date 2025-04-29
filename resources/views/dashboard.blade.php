<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard BETAAAAAAA</title>
  <style>
  * { 
    box-sizing: border-box; 
    margin: 0; 
    padding: 0; 
  }

  body { 
    font-family: Arial, sans-serif; 
    background-color: #ADBcb9; /* fondo general suave */
    height: 100vh;
    overflow: hidden;
  }

  .header { 
    background-color: #59FFD8; /* color acento */
    color: #2C3844; 
    padding: 20px; 
    text-align: center;
    position: fixed;
    width: 100%;
    top: 0;
    height: 60px;
    z-index: 1000;
    font-weight: bold;
  }

  .sidebar { 
    background-color: #2C3844; /* fondo oscuro */
    color: #fff; 
    width: 250px; 
    position: fixed; 
    top: 60px;
    bottom: 0;
    overflow-y: auto;
  }

  .sidebar a { 
    display: block; 
    color: white; 
    padding: 16px; 
    text-decoration: none; 
  }

  .sidebar a:hover { 
    background-color: #59FFD8; 
    color: #2C3844;
  }

  .main { 
    margin-left: 250px; 
    padding: 20px;
    margin-top: 60px;
    height: calc(100vh - 60px);
    overflow-y: auto;
  }

  .grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    grid-auto-rows: minmax(300px, auto);
    gap: 20px;
  }

  .card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    min-height: 350px;
    display: flex;
    flex-direction: column;
  }

  .card h2 {
    margin-bottom: 15px;
    font-size: 1.2em;
    color: #2C3844;
  }

  .chart-container {
    flex: 1;
    min-height: 250px;
    position: relative;
  }
</style>

  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
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
      <div class="card">
        <h2>Membresías Más Usadas</h2>
        <div id="membresiasChart" class="chart-container"></div>
      </div>
      
      <div class="card">
        <h2>Reservas por Mes</h2>
        <div id="reservasChart" class="chart-container"></div>
      </div>
      
      <div class="card">
        <h2>Estado de Reservas</h2>
        <div id="estadosChart" class="chart-container"></div>
      </div>
      
      <div class="card">
        <h2>Uso de Canchas</h2>
        <div id="usoCanchasChart" class="chart-container"></div>
      </div>
      
      <div class="card">
        <h2>Estados de Torneos</h2>
        <div id="torneosChart" class="chart-container"></div>
      </div>
      
      <div class="card">
        <h2>Tipos de Notificaciones</h2>
        <div id="notificacionesChart" class="chart-container"></div>
      </div>
      
      <div class="card">
        <h2>Instructores Más Activos</h2>
        <div id="instructoresChart" class="chart-container"></div>
      </div>
      
      <div class="card">
        <h2>Tipos de Eventos</h2>
        <div id="eventosChart" class="chart-container"></div>
      </div>
    </div>
  </div>

  <script>
    const membresiasLabels = ['Básica', 'Premium', 'VIP'];
    const membresiasData = [120, 75, 30];
    const reservasLabels = ['Ene', 'Feb', 'Mar', 'Abr', 'May'];
    const reservasData = [45, 60, 75, 55, 80];
    const estadosLabels = ['Confirmadas', 'Canceladas'];
    const estadosData = [85, 15];
    const usoCanchasLabels = ['Fútbol', 'Tenis', 'Pádel'];
    const usoCanchasData = [90, 45, 60];
    const torneosLabels = ['Activos', 'Finalizados', 'Pendientes'];
    const torneosData = [12, 8, 5];
    const notificacionesLabels = ['Recordatorios', 'Promociones', 'Alertas'];
    const notificacionesData = [150, 80, 45];
    const instructoresLabels = ['Juan', 'María', 'Carlos'];
    const instructoresData = [45, 38, 28];
    const eventosLabels = ['Sociales', 'Empresariales', 'Deportivos'];
    const eventosData = [25, 40, 35];

    // Configuración común de gráficos
    const commonOptions = {
      chart: {
        height: '100%',
        parentHeightOffset: 0,
        toolbar: { show: false },
        zoom: { enabled: false }
      },
      grid: {
        padding: { left: 15, right: 15 }
      },
      responsive: [{
        breakpoint: 768,
        options: { chart: { height: 300 } }
      }]
    };

    // Membresías Más Usadas
    new ApexCharts(document.querySelector("#membresiasChart"), {
      ...commonOptions,
      series: [{ data: membresiasData }],
      chart: { type: 'bar' },
      xaxis: { categories: membresiasLabels },
      colors: ['#4CAF50'],
      plotOptions: { bar: { borderRadius: 4 } }
    }).render();

    // Reservas por Mes
    new ApexCharts(document.querySelector("#reservasChart"), {
      ...commonOptions,
      chart: { type: 'line' },
      series: [{ name: 'Reservas', data: reservasData }],
      xaxis: { categories: reservasLabels },
      colors: ['#2196F3'],
      stroke: { width: 2, curve: 'smooth' }
    }).render();

    // Estado de Reservas
    new ApexCharts(document.querySelector("#estadosChart"), {
      ...commonOptions,
      chart: { type: 'donut' },
      series: estadosData,
      labels: estadosLabels,
      colors: ['#4CAF50', '#F44336'],
      plotOptions: { pie: { donut: { size: '65%' } } }
    }).render();

    // Uso de Canchas
    new ApexCharts(document.querySelector("#usoCanchasChart"), {
      ...commonOptions,
      chart: { type: 'bar' },
      series: [{ data: usoCanchasData }],
      xaxis: { categories: usoCanchasLabels },
      colors: ['#9C27B0'],
      plotOptions: { bar: { horizontal: true } }
    }).render();

    // Estados de Torneos
    new ApexCharts(document.querySelector("#torneosChart"), {
      ...commonOptions,
      chart: { type: 'pie' },
      series: torneosData,
      labels: torneosLabels,
      colors: ['#FF6384', '#36A2EB', '#FFCE56']
    }).render();

    // Tipos de Notificaciones
    new ApexCharts(document.querySelector("#notificacionesChart"), {
      ...commonOptions,
      chart: { type: 'radialBar' },
      series: notificacionesData,
      labels: notificacionesLabels,
      colors: ['#FF6384', '#4BC0C0', '#FFCE56']
    }).render();

    // Instructores Más Activos
    new ApexCharts(document.querySelector("#instructoresChart"), {
      ...commonOptions,
      chart: { type: 'radar' },
      series: [{ name: 'Clases', data: instructoresData }],
      labels: instructoresLabels,
      colors: ['#4CAF50']
    }).render();

    // Tipos de Eventos
    new ApexCharts(document.querySelector("#eventosChart"), {
      ...commonOptions,
      chart: { type: 'bar' },
      series: [{ data: eventosData }],
      xaxis: { categories: eventosLabels },
      colors: ['#F44336'],
      plotOptions: { bar: { borderRadius: 4 } }
    }).render();
  </script>
</body>
</html>