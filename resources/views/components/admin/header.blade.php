<header class="admin-header">
    <div class="logo">
        <a href="{{ route('dashboard') }}">{{ config('app.name', 'Raquet Sergius') }} - Admin</a>
    </div>
    <nav>
        @if ($empleado)
            <span>Bienvenido, {{ $empleado->nombre }}</span>
            <a href="{{ route('logout') }}"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                Cerrar Sesión
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        @else
            <a href="{{ route('login') }}">Iniciar Sesión</a>
        @endif
    </nav>
</header>
<style>
    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 20px;
        background-color: #333;
        color: white;
    }

    .admin-header .logo a {
        color: white;
        text-decoration: none;
        font-size: 24px;
    }

    .admin-header nav {
        display: flex;
        align-items: center;
    }

    .admin-header nav a {
        color: white;
        text-decoration: none;
        margin-left: 20px;
    }
