@extends('layouts.app')
@section('title', 'Editar Clase de Zumba')
@push('styles')
    <style>
        :root {
            --background-color: #1a1a1a;
            --surface-color: #242424;
            --primary-color: #00aaff;
            --text-color: #e0e0e0;
            --text-muted-color: #888;
            --border-color: #333;
            --danger-color: #ff3b30;
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
            max-width: 800px;
            /* Ancho más adecuado para formularios */
            margin: 0 auto;
        }

        h1 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
        }

        /* 2. Alertas de Notificación */
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
            border-radius: 4px;
            list-style-position: inside;
        }

        .alert-danger {
            background-color: rgba(255, 59, 48, 0.1);
            border-left-color: var(--danger-color);
            color: var(--text-color);
        }

        .alert-danger ul {
            margin-top: 0.5rem;
            padding-left: 1rem;
        }

        /* 3. Estilos de Formularios y Tarjetas */
        .form-card,
        .detail-card {
            background-color: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            /* Rejilla de dos columnas */
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
            /* Ocupa todo el ancho */
        }

        label {
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-muted-color);
        }

        input,
        select {
            width: 100%;
            padding: 0.75rem;
            box-sizing: border-box;
            border: 1px solid var(--border-color);
            background-color: var(--background-color);
            /* Fondo más oscuro para contraste */
            color: var(--text-color);
            border-radius: 8px;
            font-size: 1rem;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 170, 255, 0.3);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background-color: var(--background-color);
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        input[type="checkbox"] {
            width: 1.25em;
            height: 1.25em;
        }

        .checkbox-group label {
            margin: 0;
            color: var(--text-color);
            font-weight: normal;
        }

        /* 4. Botones */
        .form-actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }

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
            background-color: #0088cc;
            transform: translateY(-2px);
        }

        .button-secondary {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .button-secondary:hover {
            background-color: rgba(0, 170, 255, 0.1);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        /* 5. Estilos para la Página de Detalles */
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .detail-item strong {
            display: block;
            color: var(--text-muted-color);
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .detail-item span {
            font-size: 1.1rem;
        }

        .form-card {
            background-color: var(--surface-color);
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid var(--border-color);
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <div class="welcome-message">
            <h1>Editar Clase de Zumba</h1>
        </div>

        <form action="{{ route('clases-zumba.update', $clase->clase_id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-card">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                {{-- Incluir un formulario aquí o poner los campos directamente --}}
                {{-- Por simplicidad, los campos están aquí directamente --}}
                <div class="form-group"><label for="dia_semana">Día de la Semana</label><input type="text" name="dia_semana"
                        value="{{ old('dia_semana', $clase->dia_semana) }}" required></div>
                <div class="form-group"><label for="hora_inicio">Hora de Inicio</label><input type="time"
                        name="hora_inicio" value="{{ old('hora_inicio', $clase->hora_inicio) }}" required></div>
                <div class="form-group"><label for="hora_fin">Hora de Fin</label><input type="time" name="hora_fin"
                        value="{{ old('hora_fin', $clase->hora_fin) }}" required></div>
                <div class="form-group"><label for="costo">Costo (Bs.)</label><input type="number" step="0.01"
                        name="costo" value="{{ old('costo', $clase->costo) }}" required></div>
                <div class="form-group"><label for="cupo_maximo">Cupo Máximo</label><input type="number" name="cupo_maximo"
                        value="{{ old('cupo_maximo', $clase->cupo_maximo) }}" required></div>
                <div class="form-group"><label for="instructor_id">Instructor</label><select name="instructor_id" required>
                        @foreach ($instructores as $instructor)
                            <option value="{{ $instructor->instructor_id }}"
                                {{ $clase->instructor_id == $instructor->instructor_id ? 'selected' : '' }}>
                                {{ $instructor->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label for="area_id">Área</label><select name="area_id" required>
                        @foreach ($areas as $area)
                            <option value="{{ $area->area_id }}"
                                {{ $clase->area_id == $area->area_id ? 'selected' : '' }}>{{ $area->nombre }}</option>
                        @endforeach
                    </select></div>
                <div class="form-group"><label for="estado">Estado</label><select name="estado" required>
                        <option value="Programada" {{ $clase->estado == 'Programada' ? 'selected' : '' }}>Programada
                        </option>
                        <option value="Cancelada" {{ $clase->estado == 'Cancelada' ? 'selected' : '' }}>Cancelada</option>
                        <option value="Finalizada" {{ $clase->estado == 'Finalizada' ? 'selected' : '' }}>Finalizada
                        </option>
                    </select></div>
            </div>
            <div class="form-actions" style="margin-top: 2rem;">
                <button type="submit" class="button">Actualizar Clase</button>
                <a href="{{ route('clases-zumba.index') }}" class="button" style="background-color: #6c757d;">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
