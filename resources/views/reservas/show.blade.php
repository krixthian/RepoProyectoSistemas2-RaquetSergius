<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle Reserva #{{ $reserva->reserva_id }}</title>
    <style>
        body { font-family:sans-serif; margin:20px; background:#2C3844; color:white; }
        h1 { color:#59FFD8; }
        .detail { margin-bottom:20px; padding-bottom:15px; border-bottom:1px solid #ADBCB9; }
        h3 { margin-top:0; color:#59FFD8; }
        ul { list-style:none; padding:0; }
        li { margin-bottom:8px; }
        strong { display:inline-block; width:160px; color:#ADBCB9; }
        a { display:inline-block; padding:8px 12px; background:#59FFD8; color:#2C3844; text-decoration:none; border-radius:4px; margin-top:10px; }
        a:hover { background:#ADBCB9; }
    </style>
</head>
<body>
    <h1>Detalle Reserva #{{ $reserva->reserva_id }}</h1>

    <div class="detail">
        <h3>Datos Generales</h3>
        <ul>
            <li><strong>Cliente:</strong> {{ $reserva->cliente->nombre ?? '–' }}</li>
            <li><strong>Cancha:</strong> {{ $reserva->cancha->nombre ?? '–' }}</li>
            <li>
                <strong>Inicio:</strong>
                {{ optional($reserva->fecha)->format('d/m/Y') ?? '–' }}
                {{ $reserva->hora_inicio }}
            </li>
            <li>
                <strong>Fin:</strong>
                {{ optional($reserva->fecha)->format('d/m/Y') ?? '–' }}
                {{ $reserva->hora_fin }}
            </li>
            <li><strong>Monto Total:</strong> {{ number_format($reserva->monto,2) }}</li>
            <li><strong>Estado:</strong> {{ $reserva->estado }}</li>
            <li><strong>Método Pago:</strong> {{ $reserva->metodo_pago ?? '–' }}</li>
            <li><strong>Pago Completo:</strong> {{ $reserva->pago_completo ? 'Sí' : 'No' }}</li>
            <li>
                <strong>Creado:</strong>
                {{ optional($reserva->created_at)->format('d/m/Y H:i') ?? '–' }}
            </li>
            <li>
                <strong>Actualizado:</strong>
                {{ optional($reserva->updated_at)->format('d/m/Y H:i') ?? '–' }}
            </li>
        </ul>
    </div>

    <a href="{{ route('reservas.index') }}">Volver al listado</a>
</body>
</html>
