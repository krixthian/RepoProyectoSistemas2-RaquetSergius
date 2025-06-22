@extends('layouts.app')

@section('title', 'Canjear Premios')

@push('styles')
    <style>
        .form-card {
            background-color: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            margin-top: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            font-weight: 500;
            color: var(--text-muted-color);
            margin-bottom: 0.5rem;
            display: block;
        }

        select {
            width: 100%;
            padding: 0.75rem;
            box-sizing: border-box;
            border: 1px solid var(--border-color);
            background-color: var(--background-color);
            color: var(--text-color);
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
        }

        .button {
            padding: 0.75rem 1.5rem;
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        #premios-lista {
            margin-top: 1rem;
        }

        .premio-item {
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <h1>Canjear Premios para Cliente</h1>

        <div class="form-card">
            <form action="{{ route('puntos.canjear.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="cliente_id">Seleccionar Cliente</label>
                    <select name="cliente_id" id="cliente_id" required>
                        <option value="">-- Elige un cliente --</option>
                        @foreach ($clientes as $cliente)
                            <option value="{{ $cliente->cliente_id }}" data-puntos="{{ $cliente->puntos }}"
                                {{ old('cliente_id') == $cliente->cliente_id ? 'selected' : '' }}>
                                {{ $cliente->nombre }} (Puntos: {{ $cliente->puntos }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="premio_id">Seleccionar Premio</label>
                    <select name="premio_id" id="premio_id" required disabled>
                        <option value="">-- Selecciona un cliente primero --</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button" id="btn-canjear" disabled>Canjear Premio</button>
                    <a href="{{ route('clientes.opciones') }}" class="button button-secondary"
                        style="background-color: #6c757d;">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const clienteSelect = document.getElementById('cliente_id');
                const premioSelect = document.getElementById('premio_id');
                const btnCanjear = document.getElementById('btn-canjear');
                const premios = @json($premios);

                clienteSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const puntosCliente = parseInt(selectedOption.dataset.puntos) || 0;

                    // Limpiar y deshabilitar el select de premios
                    premioSelect.innerHTML = '<option value="">-- Selecciona un premio --</option>';
                    premioSelect.disabled = true;
                    btnCanjear.disabled = true;

                    if (this.value) {
                        // Habilitar y poblar el select de premios
                        premioSelect.disabled = false;
                        premios.forEach(premio => {
                            if (puntosCliente >= premio.puntos_requeridos) {
                                const option = new Option(
                                    `${premio.nombre} (${premio.puntos_requeridos} puntos)`, premio
                                    .premio_id);
                                premioSelect.appendChild(option);
                            }
                        });
                    }
                });

                premioSelect.addEventListener('change', function() {
                    btnCanjear.disabled = !this.value;
                });
            });
        </script>
    @endpush
@endsection
