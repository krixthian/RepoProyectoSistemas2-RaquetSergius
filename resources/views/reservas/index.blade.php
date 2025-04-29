<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Reservas</title>
    <style>
        body { font-family: sans-serif; margin:20px; background:#2C3844; color:white; }
        h1 { color:#59FFD8; }
        .alert { padding:10px; margin-bottom:15px; border-radius:4px; }
        .alert-success { background:#59FFD8; color:#2C3844; border:1px solid #ADBCB9; }
        .alert-danger  { background:#f2dede; color:#a94442; border:1px solid #ebccd1; }
        .button-link {
            display:inline-block; padding:10px 15px; background:#59FFD8;
            color:#2C3844; border-radius:4px; text-decoration:none; font-weight:bold;
        }
        .button-link:hover { background:#ADBCB9; }
        table {
            width:100%; border-collapse:collapse; margin-top:15px; font-size:.9em;
        }
        th, td {
            border:1px solid #ADBCB9; padding:6px; vertical-align:top;
        }
        th { background:#59FFD8; color:#2C3844; white-space:nowrap; }
        td { background:#2C3844; }
        .actions a {
            margin-right:8px; color:#59FFD8; text-decoration:none;
        }
        .actions a:hover { color:#ADBCB9; }
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

    <a href="{{ route('reservas.create') }}" class="button-link">Crear Nueva Reserva</a>

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
                    <td>{{ $res->cliente->nombre ?? '–' }}</td>
                    <td>{{ $res->cancha->nombre ?? '–' }}</td>
                    <td>{{ $res->fecha->format('d/m/Y') }} {{ $res->hora_inicio }}</td>
                    <td>{{ $res->fecha->format('d/m/Y') }} {{ $res->hora_fin }}</td>
                    <td>{{ number_format($res->monto,2) }}</td>
                    <td>{{ $res->estado }}</td>
                    <td>{{ $res->pago_completo ? 'Sí' : 'No' }}</td>
                    <td>{{ $res->metodo_pago ?? '–' }}</td>
                    <td>{{ $res->created_at->format('d/m/Y H:i') }}</td>
                    <td class="actions">
                        <a href="{{ route('reservas.show', $res->reserva_id) }}">Ver</a>
                        <a href="{{ route('reservas.edit', $res->reserva_id) }}">Editar</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11">No hay reservas registradas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
