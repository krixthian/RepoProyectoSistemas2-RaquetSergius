@extends('layouts.app')

@section('title', 'Modificar Puntos de Cliente')

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

        select,
        input,
        textarea {
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
    </style>
@endpush

@section('content')
    <div class="container">
        <h1>Modificar Puntos de Cliente</h1>

        <div class="form-card">
            <form action="{{ route('puntos.sumar-restar.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="cliente_id">Seleccionar Cliente</label>
                    <select name="cliente_id" id="cliente_id" required>
                        <option value="">-- Elige un cliente --</option>
                        @foreach ($clientes as $cliente)
                            <option value="{{ $cliente->cliente_id }}"
                                {{ old('cliente_id') == $cliente->cliente_id ? 'selected' : '' }}>
                                {{ $cliente->nombre }} (Puntos: {{ $cliente->puntos }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="accion">Acción</label>
                    <select name="accion" id="accion" required>
                        <option value="sumar" {{ old('accion') == 'sumar' ? 'selected' : '' }}>Sumar Puntos</option>
                        <option value="restar" {{ old('accion') == 'restar' ? 'selected' : '' }}>Restar Puntos</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="puntos">Cantidad de Puntos</label>
                    <input type="number" name="puntos" id="puntos" value="{{ old('puntos') }}" min="1" required>
                </div>

                <div class="form-group">
                    <label for="motivo">Motivo</label>
                    <textarea name="motivo" id="motivo" rows="3" required
                        placeholder="Ej: Bonificación especial, corrección de saldo, etc.">{{ old('motivo') }}</textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button">Aplicar Cambios</button>
                    <a href="{{ route('clientes.opciones') }}" class="button button-secondary"
                        style="background-color: #6c757d;">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
