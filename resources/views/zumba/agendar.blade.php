@extends('layouts.app')

@section('title', 'Definir Nuevo Horario de Clase')

@push('styles')
<style>
    /* 1. Paleta de Colores y Estilos Base */
    :root {
        --background-color: #1a1a1a;
        --surface-color: #242424;
        --primary-color: #00aaff;
        --text-color: #e0e0e0;
        --text-muted-color: #888;
        --border-color: #333;
        --danger-color: #ff3b30;
    }

    /* Aplica el fondo al cuerpo de la página principal si no está ya definido en app.blade.php */
    body {
        background-color: var(--background-color);
        color: var(--text-color);
    }

    .container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 0 2rem;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        line-height: 1.6;
    }

    h1 {
        color: var(--primary-color);
        font-weight: 600;
        margin-bottom: 2rem;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 1rem;
    }
    
    p {
        color: var(--text-muted-color);
        margin-bottom: 2rem;
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
    .alert-danger strong {
        font-weight: 600;
    }
    .alert-danger ul {
        margin-top: 0.5rem;
        padding-left: 1rem;
        margin-bottom: 0;
    }

    /* 3. Estilos de Formularios y Tarjetas */
    .form-card {
        background-color: var(--surface-color);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 2rem;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Rejilla adaptable */
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .form-grid:last-of-type {
        margin-bottom: 0;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    label {
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--text-muted-color);
    }

    input, select {
        width: 100%;
        padding: 0.75rem;
        box-sizing: border-box;
        border: 1px solid var(--border-color);
        background-color: var(--background-color);
        color: var(--text-color);
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    input:focus, select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(0, 170, 255, 0.3);
    }

    /* 4. Botones */
    .form-actions {
        margin-top: 2rem;
        display: flex;
        justify-content: flex-start; /* Alinea el botón a la izquierda */
        gap: 1rem;
    }

    .button {
        display: inline-block;
        padding: 0.75rem 1.5rem;
        background-color: var(--primary-color);
        color: #ffffff; /* Texto blanco para mejor contraste */
        border: none;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s ease, transform 0.2s ease;
    }

    .button:hover {
        background-color: #0088cc;
        transform: translateY(-2px);
    }

</style>
@endpush

@section('content')
<div class="container">
    <h1>Definir Nuevo Horario de Clase de Zumba</h1>
    <p>Completa la información para crear un nuevo horario semanal.</p>

    <div class="form-card">
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>¡Ups! Hubo algunos problemas con tu entrada.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('zumba.agendar.store') }}" method="POST">
            @csrf

            <div class="form-grid">
                <div class="form-group">
                    <label for="instructor_id">Instructor</label>
                    <select id="instructor_id" name="instructor_id" required>
                        <option value="">-- Selecciona un instructor --</option>
                        @foreach ($instructores as $instructor)
                            <option value="{{ $instructor->instructor_id }}" {{ old('instructor_id') == $instructor->instructor_id ? 'selected' : '' }}>
                                {{ $instructor->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="area_id">Área / Salón</label>
                    <select id="area_id" name="area_id" required>
                        <option value="">-- Selecciona un área --</option>
                        @foreach ($areas as $area)
                            <option value="{{ $area->area_id }}" {{ old('area_id') == $area->area_id ? 'selected' : '' }}>
                                {{ $area->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="diasemama">Día de la Semana</label>
                    <select id="diasemama" name="diasemama" required>
                        <option value="">-- Selecciona un día --</option>
                        @foreach($diasDeLaSemana as $dia)
                            <option value="{{ $dia }}" {{ old('diasemama') == $dia ? 'selected' : '' }}>{{ $dia }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="hora_inicio">Hora de Inicio</label>
                    <input type="time" id="hora_inicio" name="hora_inicio" value="{{ old('hora_inicio') }}" required>
                </div>

                <div class="form-group">
                    <label for="hora_fin">Hora de Fin</label>
                    <input type="time" id="hora_fin" name="hora_fin" value="{{ old('hora_fin') }}" required>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="cupo_maximo">Cupo Máximo</label>
                    <input type="number" id="cupo_maximo" name="cupo_maximo" value="{{ old('cupo_maximo') }}" required>
                </div>

                <div class="form-group">
                    <label for="precio">Precio por clase (Bs.)</label>
                    <input type="number" step="0.01" id="precio" name="precio" value="{{ old('precio') }}" required>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="button">Definir Horario</button>
            </div>
        </form>
    </div>
</div>
@endsection