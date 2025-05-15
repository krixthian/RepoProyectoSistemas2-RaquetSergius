<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Raquet Sergius')</title> {{-- Título por defecto, se puede cambiar por vista --}}

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Si tienes tu propio CSS, enlazarlo así: --}}
    {{-- <link href="{{ asset('css/style.css') }}" rel="stylesheet"> --}}

    @stack('styles') {{-- Para añadir estilos específicos de una página --}}
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">Raquet Sergius</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a> {{-- Asumiendo que tienes esta ruta --}}
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('torneos.index') }}">Torneos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('equipos.index') }}">Equipos</a>
                    </li>
                    {{-- Aquí puedes añadir más enlaces de navegación --}}
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                        
                    @else
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <a class="nav-link" href="{{ route('logout') }}"
                                   onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                    Logout ({{ Auth::user()->usuario }}) {{-- Asumiendo que tu modelo User/Empleado tiene 'usuario' --}}
                                </a>
                            </form>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        @yield('content') {{-- Aquí se inyectará el contenido principal de tus vistas --}}
    </div>

    <footer class="text-center mt-5 py-3">
        <p>&copy; {{ date('Y') }} Raquet Sergius. Todos los derechos reservados.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    {{-- Si tienes tu propio JS, enlazarlo así: --}}
    {{-- <script src="{{ asset('js/main.js') }}"></script> --}}
    @stack('scripts') {{-- Para añadir scripts específicos de una página --}}
</body>
</html>