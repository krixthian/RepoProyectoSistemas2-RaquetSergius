@extends('layouts.app')

@section('title', 'Revisión de Pagos - Inscripciones Zumba')

@push('styles')
    {{-- Utilizando los mismos estilos que la vista de reservas --}}
    <style>
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
            transition: all 0.2s ease;
        }

        .button:hover,
        .button-link:hover {
            background-color: #c20069;
            transform: translateY(-2px);
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
            font-size: 0.9em;
            background-color: var(--surface-color);
            border-radius: 8px;
            overflow: hidden;
        }

        th,
        td {
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

        .actions a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .actions a:hover {
            color: #c20069;
        }
    </style>
@endpush

@section('content')
    <h1><i class="fas fa-receipt"></i> Revisión de Pagos de Zumba</h1>
    <p style="color: var(--text-muted-color);">Inscripciones pendientes agrupadas por comprobante de pago.</p>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Formulario de Búsqueda --}}
    <form action="{{ route('zumba.pendientes') }}" method="GET" class="search-form">
        <input type="text" name="cliente_nombre" placeholder="Buscar por nombre de cliente..."
            value="{{ request('cliente_nombre') }}">
        <button type="submit" class="button">Buscar</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Teléfono</th>
                <th>Clases en Grupo</th>
                <th>Monto Total Pagado</th>
                <th>Fecha de Envío</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($comprobantes as $comprobante)
                <tr>
                    <td>{{ $comprobante->cliente->nombre ?? 'N/A' }}</td>
                    <td>{{ $comprobante->cliente->telefono ?? 'N/A' }}</td>
                    <td>{{ $comprobante->total_clases }} clase(s)</td>
                    <td>{{ number_format($comprobante->monto_total, 2) }} Bs.</td>
                    <td>{{ \Carbon\Carbon::parse($comprobante->fecha_primera_inscripcion)->isoFormat('dddd D [de] MMMM, HH:mm') }}
                    </td>
                    <td class="actions">
                        {{-- Codificamos la ruta del comprobante para pasarla en la URL --}}
                        @php
                            $comprobanteHash = str_replace('/', '-', $comprobante->ruta_comprobante_pago);
                        @endphp
                        <a
                            href="{{ route('zumba.verComprobante', ['cliente_id' => $comprobante->cliente_id, 'comprobante_hash' => $comprobanteHash]) }}">Ver
                            y Revisar</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem;">
                        No hay pagos de inscripciones de Zumba pendientes de revisión.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

@endsection
