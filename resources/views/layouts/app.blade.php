<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Raquet Sergius - Admin')</title>

    {{-- Bootstrap CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- FontAwesome CDN --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    {{-- Estilos personalizados y para sobrescribir Bootstrap si es necesario --}}
    <style>
        :root {
            --background-color: #1a1a1a;
            --surface-color: #242424;
            --primary-color: #e6007e;
            /* Un rosa/magenta distintivo para Zumba */
            --text-color: #e0e0e0;
            --text-muted-color: #888;
            --border-color: #333;
            --blueraquet-color: #00aaff;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
        }

        .main-content {
            padding: 2rem;
        }

        h1 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 2rem;
        }

        .navbar {
            background-color: #343a40 !important;
            /* Navbar oscura */
            border-bottom: 3px solid #00aaff;
            /* Acento de tu diseño original */
        }

        .navbar .navbar-brand,
        .navbar .nav-link {
            color: #f8f9fa !important;
        }

        .navbar .nav-link:hover {
            color: #59FFD8 !important;
        }

        .navbar .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28248, 249, 250, 0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .main-content {
            flex: 1;
            /* Hace que el contenido principal ocupe el espacio restante */
            padding-top: 20px;
            /* Espacio debajo de la navbar */
            padding-bottom: 20px;
        }

        footer {
            background-color: #343a40;
            color: #f8f9fa;
            padding: 1rem 0;
            font-size: 0.9rem;
        }

        .option-card {
            /* Estilos que teníamos para el panel de opciones */
            background-color: var(--border-color);
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            text-decoration: none;
            display: block;
            margin-bottom: 20px;
            color: var(--text-color);
            /* Espacio entre tarjetas si están en columna */
        }

        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .option-card h2 {
            font-size: 1.5em;
            color: var(--text-color);
            margin-bottom: 10px;

        }

        .option-card p {
            font-size: 0.95em;
            color: var(--text-color);
            margin-bottom: 15px;
            min-height: 40px;
        }

        .option-card .status {
            font-size: 0.8em;
            font-weight: bold;
        }

        .status-implemented {
            color: #38A169;
        }

        .status-pending {
            color: #D69E2E;
        }

        .btn-manage {
            display: inline-block;
            padding: 8px 15px;
            background-color: #2C3844;
            color: #59FFD8;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }

        .btn-manage:hover {
            background-color: #1a2229;
        }

        .btn-manage.disabled {
            background-color: #A0AEC0;
            color: #E2E8F0;
            cursor: not-allowed;
        }
    </style>

    @stack('styles') {{-- Para añadir estilos específicos de una página --}}
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ Auth::check() ? route('admin.empleados.index') : url('/') }}">Raquet
                Sergius</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    @auth {{-- Solo mostrar estos enlaces si el usuario está autenticado --}}
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('admin.empleados.index') }}">Panel Principal</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('dashboard') }}">Dashboard (Gráficos)</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('reservas.index') }}">Reservas</a>
                        </li>
                        {{-- Puedes añadir más enlaces para Torneos, Equipos, etc. cuando tengas sus rutas de admin --}}
                        {{-- <li class="nav-item"><a class="nav-link" href="#">Torneos</a></li> --}}
                        {{-- <li class="nav-item"><a class="nav-link" href="#">Equipos</a></li> --}}
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <a class="nav-link" href="{{ route('logout') }}"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                    Logout ({{ Auth::user()->nombre ?? Auth::user()->usuario }})
                                </a>
                            </form>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <div class="container main-content">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>¡Error!</strong> Por favor, revisa los siguientes mensajes:
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content') {{-- Aquí se inyectará el contenido principal de tus vistas --}}
    </div>

    <footer class="text-center mt-auto py-3">
        <p>&copy; {{ date('Y') }} Raquet Sergius. Todos los derechos reservados.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    @stack('scripts') {{-- Para añadir scripts específicos de una página --}}
</body>

</html>
