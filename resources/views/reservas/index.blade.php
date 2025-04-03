<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Reservas</title>
    <style> /* Estilos básicos */ body { font-family: sans-serif; margin: 20px; } table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 0.9em;} th, td { border: 1px solid #ddd; padding: 6px; text-align: left; vertical-align: top;} th { background-color: #f2f2f2; white-space: nowrap;} .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; } .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; } .button-link { display: inline-block; padding: 8px 12px; background-color: #28a745; color: white; text-decoration: none; border-radius: 4px; font-size:0.9em; } ul { margin: 0; padding-left: 18px; list-style: disc;} .actions a {margin-right: 5px; text-decoration: none;} </style>
</head>
<body>
    <h1>Listado de Reservas</h1>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <p>
        <a href="{{ route('reservas.create') }}" class="button-link">Crear Nueva Reserva</a>
    </p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Inicio</th>
                <th>Fin</th>
                <th>Monto Total</th>
                <th>Estado</th>
                <th>Pago Completo</th>
                <th>Método Pago</th>
                <th>Canchas (Precio Específico)</th>
                <th>Creado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reservas as $reserva)
                <tr>
                    <td>{{ $reserva->reserva_id }}</td>
                    {{-- Accede al nombre del cliente a través de la relación --}}
                    <td>{{ $reserva->cliente ? $reserva->cliente->nombre : 'Cliente no encontrado' }}</td>
                    <td>{{ $reserva->fecha_hora_inicio->format('d/m/Y H:i') }}</td>
                    <td>{{ $reserva->fecha_hora_fin->format('d/m/Y H:i') }}</td>
                    <td>{{ number_format($reserva->monto, 2) }}</td>
                    <td>{{ $reserva->estado }}</td>
                    <td>{{ $reserva->pago_completo ? 'Sí' : 'No' }}</td>
                    <td>{{ $reserva->metodo_pago ?? '-' }}</td>
                    <td>
                        {{-- Accede a las canchas y su precio_total desde la tabla pivote --}}
                        @if ($reserva->canchas->isNotEmpty())
                            <ul>
                            @foreach ($reserva->canchas as $cancha)
                                <li>
                                    {{ $cancha->nombre ?? 'Cancha no encontrada' }}
                                    ({{ number_format($cancha->pivot->precio_total, 2) }})
                                </li>
                            @endforeach
                            </ul>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $reserva->created_at->format('d/m/Y H:i') }}</td>
                    <td class="actions">
                        <a href="{{ route('reservas.show', $reserva->reserva_id) }}">Ver</a>
                        {{-- <a href="{{ route('reservas.edit', $reserva->reserva_id) }}">Editar</a> --}}
                        {{-- Formulario DELETE si implementas destroy --}}
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