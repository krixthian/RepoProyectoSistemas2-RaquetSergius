
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Listado de Reservas</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 20px;
            background: #2C3844;
            color: white;
        }

        h1 {
            color: #59FFD8;
        }

        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .alert-success {
            background: #59FFD8;
            color: #2C3844;
            border: 1px solid #ADBCB9;
        }

        .alert-danger {
            background: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }

        .button-link {
            display: inline-block;
            padding: 10px 15px;
            background: #59FFD8;
            color: #2C3844;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
        }

        .button-link:hover {
            background: #ADBCB9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: .9em;
        }

        th,
        td {
            border: 1px solid #ADBCB9;
            padding: 6px;
            vertical-align: top;
        }

        th {
            background: #59FFD8;
            color: #2C3844;
            white-space: nowrap;
        }

        td {
            background: #2C3844;
        }

        .actions a {
            margin-right: 8px;
            color: #59FFD8;
            text-decoration: none;
        }

        .actions a:hover {
            color: #ADBCB9;
        }

        .search-form {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-form input[type="text"] {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ADBCB9;
            background: #2C3844;
            color: white;
            flex-grow: 1;
        }

        .search-form .button-link {
            padding: 8px 12px;
        }
    </style>
</head>

<body>
    <h1>Listado de Reservas</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->has('error_general'))
        <div class="alert alert-danger">{{ $errors->first('error_general') }}</div>
    @endif

    <a href="{{ route('reservas.create') }}" class="button-link" style="margin-bottom:20px;">Crear Nueva Reserva</a>

    {{-- INICIO: Formulario de Búsqueda por Nombre de Cliente --}}
    <form action="{{ route('reservas.index') }}" method="GET" class="search-form">
        <input type="text" name="cliente_nombre" placeholder="Buscar por nombre de cliente..."
            value="{{ request('cliente_nombre') }}">
        <button type="submit" class="button-link">Buscar</button>
        @if(request('cliente_nombre'))
            <a href="{{ route('reservas.index') }}" class="button-link" style="background: #8a6d3b; color:white;">Limpiar
                Filtro</a>
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
                <th>Pago Completo</th>
                <th>Método Pago</th>
                <th>Creado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reservas as $res)
                <tr>
                    <td>{{ $res->reserva_id }}</td>
                    <td>{{ $res->cliente->nombre ?? '–' }} {{ $res->cliente->apellido ?? '' }}</td> {{-- Asumiendo que
                    cliente puede tener apellido --}}
                    <td>{{ $res->cancha->nombre ?? '–' }}</td>
                    <td>{{ \Carbon\Carbon::parse($res->fecha)->format('d/m/Y') }}
                        {{ \Carbon\Carbon::parse($res->hora_inicio)->format('H:i') }}</td>
                    <td>{{ \Carbon\Carbon::parse($res->fecha)->format('d/m/Y') }}
                        {{ \Carbon\Carbon::parse($res->hora_fin)->format('H:i') }}</td>
                    <td>{{ number_format($res->monto, 2) }}</td>
                    <td>{{ $res->estado }}</td>
                    <td>{{ $res->pago_completo ? 'Sí' : 'No' }}</td>
                    <td>{{ $res->metodo_pago ?? '–' }}</td>
                    <td>{{ \Carbon\Carbon::parse($res->created_at)->format('d/m/Y H:i') }}</td>
                    <td class="actions">
                        <a href="{{ route('reservas.show', $res->reserva_id) }}">Ver</a>
                        <a href="{{ route('reservas.edit', $res->reserva_id) }}">Editar</a>

                        <span class="eliminar-link" onclick="mostrarConfirmacion(this)">Eliminar</span>

                        <form action="{{ route('reservas.destroy', $res->reserva_id) }}" method="POST" class="form-eliminar"
                            style="display:none; margin-top:5px;">
                            @csrf
                            @method('DELETE')
                            <span>¿Confirmar? </span>
                            <button type="submit" class="button-link"
                                style="background:#f77; color:white; padding:5px 10px;">Sí</button>
                            <button type="button" class="button-link"
                                style="background:#888; color:white; padding:5px 10px;"
                                onclick="cancelarConfirmacion(this)">No</button>
                        </form>
                    </td>


                </tr>
            @empty
                <tr>
                    <td colspan="11">
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
</body>
<script>
    function mostrarConfirmacion(elemento) {
        const form = elemento.nextElementSibling;
        form.style.display = 'inline-block';
        elemento.style.display = 'none';
    }

    function cancelarConfirmacion(boton) {
        const form = boton.closest('.form-eliminar');
        form.previousElementSibling.style.display = 'inline';
        form.style.display = 'none';
    }
</script>

</html>