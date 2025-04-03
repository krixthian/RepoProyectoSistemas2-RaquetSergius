<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle Reserva #{{ $reserva->reserva_id }}</title>
     <style> /* Estilos básicos */ body { font-family: sans-serif; margin: 20px; } .detail-section { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;} h3 { margin-top: 0;} ul { list-style: none; padding: 0;} li { margin-bottom: 8px;} strong { display: inline-block; width: 150px; } </style>
</head>
<body>
    <h1>Detalle Reserva #{{ $reserva->reserva_id }}</h1>

    <div class="detail-section">
        <h3>Datos Generales</h3>
        <ul>
            <li><strong>Cliente:</strong> {{ $reserva->cliente ? $reserva->cliente->nombre : 'N/A' }}</li>
            <li><strong>Inicio:</strong> {{ $reserva->fecha_hora_inicio->format('d/m/Y H:i') }}</li>
            <li><strong>Fin:</strong> {{ $reserva->fecha_hora_fin->format('d/m/Y H:i') }}</li>
            <li><strong>Monto Total:</strong> {{ number_format($reserva->monto, 2) }}</li>
            <li><strong>Estado:</strong> {{ $reserva->estado }}</li>
            <li><strong>Método Pago:</strong> {{ $reserva->metodo_pago ?? '-' }}</li>
            <li><strong>Pago Completo:</strong> {{ $reserva->pago_completo ? 'Sí' : 'No' }}</li>
            <li><strong>Creado:</strong> {{ $reserva->created_at->format('d/m/Y H:i') }}</li>
            <li><strong>Actualizado:</strong> {{ $reserva->updated_at->format('d/m/Y H:i') }}</li>
        </ul>
    </div>

     <div class="detail-section">
        <h3>Cancha(s) Asignada(s)</h3>
        @if ($reserva->canchas->isNotEmpty())
            <ul>
                @foreach ($reserva->canchas as $cancha)
                    <li>
                       <strong>{{ $cancha->nombre ?? 'N/A' }}:</strong>
                       Precio específico: {{ number_format($cancha->pivot->precio_total, 2) }}
                       <small>(ID Asignación: {{ $cancha->pivot->reserva_cancha_id }})</small>
                    </li>
                @endforeach
            </ul>
        @else
            <p>No hay canchas asignadas específicamente a esta reserva.</p>
        @endif
    </div>

    <a href="{{ route('reservas.index') }}">Volver al listado</a>
    {{-- <a href="{{ route('reservas.edit', $reserva->reserva_id) }}" style="margin-left: 15px;">Editar Reserva</a> --}}

</body>
</html>