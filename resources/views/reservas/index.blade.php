<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Listado de Reservas</title>
    <style>
        /* 1. Paleta de Colores y Estilos Base */
        :root {
            --background-color: #1a1a1a; /* Un negro más suave */
            --surface-color: #242424;   /* Color para elementos como la tabla */
            --primary-color: #00aaff;    /* Un azul vibrante como color de acento */
            --text-color: #e0e0e0;       /* Texto principal, un gris muy claro */
            --text-muted-color: #888;    /* Texto secundario */
            --border-color: #333;       /* Bordes sutiles */
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 2rem;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 2rem;
        }

        /* 2. Alertas de Notificación */
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
            border-radius: 4px;
        }

        .alert-success {
            background-color: rgba(0, 170, 255, 0.1);
            border-left-color: var(--primary-color);
            color: var(--text-color);
        }

        .alert-danger {
            background-color: rgba(255, 59, 48, 0.1);
            border-left-color: #ff3b30;
            color: var(--text-color);
        }

        /* 3. Formularios y Botones */
        .button,
        .button-link {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .button:hover,
        .button-link:hover {
            background-color: #0088cc; /* Un poco más oscuro al pasar el cursor */
            transform: translateY(-2px);
        }

        .button-secondary {
            background-color: var(--surface-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }
        .button-secondary:hover {
            background-color: var(--border-color);
        }
        
        .search-form {
            margin: 2rem 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .search-form input[type="text"] {
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background-color: var(--surface-color);
            color: var(--text-color);
            flex-grow: 1;
            font-size: 1rem;
        }

        .search-form input[type="text"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 170, 255, 0.3);
        }

        /* 4. Tabla Minimalista */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
            font-size: 0.9em;
            background-color: var(--surface-color);
            border-radius: 8px;
            overflow: hidden; /* Para que el radio se aplique a las celdas */
        }

        th, td {
            padding: 1rem;
            text-align: left;
            vertical-align: middle;
        }

        thead {
            border-bottom: 2px solid var(--border-color);
        }

        th {
            color: var(--text-muted-color);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid var(--border-color);
        }
        
        tbody tr:last-child {
            border-bottom: none;
        }

        tbody tr:hover {
            background-color: var(--border-color);
        }

        /* 5. Enlaces de Acciones y Formulario de Eliminación */
        .actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .actions a, .eliminar-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .actions a:hover, .eliminar-link:hover {
            color: #0088cc;
        }

        .form-eliminar {
            display: none;
            align-items: center;
            gap: 0.5rem;
        }

        .form-eliminar span {
            color: var(--text-muted-color);
        }
        
        .form-eliminar .button {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
        }
        
        .button-danger {
            background-color: #ff3b30;
        }
        .button-danger:hover {
            background-color: #c7001e;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Listado de Reservas</h1>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->has('error_general'))
            <div class="alert alert-danger">{{ $errors->first('error_general') }}</div>
        @endif
        
        <a href="{{ route('reservas.create') }}" class="button-link">Crear Nueva Reserva</a>

        {{-- INICIO: Formulario de Búsqueda --}}
        <form action="{{ route('reservas.index') }}" method="GET" class="search-form">
            <input type="text" name="cliente_nombre" placeholder="Buscar por nombre de cliente..."
                value="{{ request('cliente_nombre') }}">
            <button type="submit" class="button">Buscar</button>
            @if(request('cliente_nombre'))
                <a href="{{ route('reservas.index') }}" class="button-link button-secondary">Limpiar Filtro</a>
            @endif
        </form>
        {{-- FIN: Formulario de Búsqueda --}}

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Cancha</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th>Pago</th>
                    <th>Método</th>
                    <th>Creado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reservas as $res)
                    <tr>
                        <td>{{ $res->reserva_id }}</td>
                        <td>{{ $res->cliente->nombre ?? '–' }} {{ $res->cliente->apellido ?? '' }}</td>
                        <td>{{ $res->cancha->nombre ?? '–' }}</td>
                        <td>{{ \Carbon\Carbon::parse($res->fecha)->format('d/m/y') }} {{ \Carbon\Carbon::parse($res->hora_inicio)->format('H:i') }}</td>
                        <td>{{ \Carbon\Carbon::parse($res->fecha)->format('d/m/y') }} {{ \Carbon\Carbon::parse($res->hora_fin)->format('H:i') }}</td>
                        <td>{{ number_format($res->monto, 2) }}</td>
                        <td>{{ $res->estado }}</td>
                        <td>{{ $res->pago_completo ? 'Sí' : 'No' }}</td>
                        <td>{{ $res->metodo_pago ?? '–' }}</td>
                        <td>{{ \Carbon\Carbon::parse($res->created_at)->format('d/m/y H:i') }}</td>
                        <td class="actions">
                            <a href="{{ route('reservas.show', $res->reserva_id) }}">Ver</a>
                            <a href="{{ route('reservas.edit', $res->reserva_id) }}">Editar</a>
                            <span class="eliminar-link" onclick="mostrarConfirmacion(this)">Eliminar</span>
                            
                            <form action="{{ route('reservas.destroy', $res->reserva_id) }}" method="POST" class="form-eliminar">
                                @csrf
                                @method('DELETE')
                                <span>¿Confirmar?</span>
                                <button type="submit" class="button button-danger">Sí</button>
                                <button type="button" class="button button-secondary" onclick="cancelarConfirmacion(this)">No</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 2rem;">
                            @if(request('cliente_nombre'))
                                No hay reservas que coincidan con "{{ request('cliente_nombre') }}".
                            @else
                                No hay reservas registradas.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
<script>
    function mostrarConfirmacion(elemento) {
        // Oculta los otros enlaces de acción
        elemento.parentElement.querySelectorAll('a, .eliminar-link').forEach(el => el.style.display = 'none');
        // Muestra el formulario de confirmación
        const form = elemento.nextElementSibling;
        form.style.display = 'flex'; 
    }

    function cancelarConfirmacion(boton) {
        const form = boton.closest('.form-eliminar');
        // Oculta el formulario
        form.style.display = 'none';
        // Muestra los enlaces de acción nuevamente
        form.parentElement.querySelectorAll('a, .eliminar-link').forEach(el => el.style.display = 'inline');
    }
</script>

</html>