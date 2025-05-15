<!-- resources/views/layouts/app.blade.php -->

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'Mi Aplicación')</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e0f7fa; /* celeste piscina claro */
            color: #004d40; /* verde oscuro para texto */
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #b2dfdb; /* celeste piscina más oscuro */
            padding: 1rem;
            text-align: center;
            color: #004d40;
            font-weight: bold;
            font-size: 1.5rem;
        }
        main {
            background-color: #f5f5dc; /* beige */
            padding: 2rem;
            min-height: 80vh;
        }
        footer {
            background-color: #b2dfdb;
            padding: 1rem;
            text-align: center;
            color: #004d40;
            font-size: 0.9rem;
        }
        a {
            color: #00796b;
            text-decoration: none;
            font-weight: 600;
        }
        a:hover {
            text-decoration: underline;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <header>
        Raquet Sergius - Gestión Clientes
    </header>
    <main>
        <div class="container">
            @if(session('success'))
                <div style="background-color: #a5d6a7; padding: 10px; margin-bottom: 20px; border-radius: 5px; color: #1b5e20;">
                    {{ session('success') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>
    <footer>
        &copy; {{ date('Y') }} Raquet Sergius. Todos los derechos reservados.
    </footer>
</body>
</html>
