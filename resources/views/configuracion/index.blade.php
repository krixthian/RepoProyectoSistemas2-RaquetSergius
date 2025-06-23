@extends('layouts.app')

@section('title', 'Configuración del Sitio')

@push('styles')
    <style>
        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .config-card {
            background-color: var(--surface-color);
            padding: 2rem;
            border-radius: 12px;
        }

        .config-card h3 {
            color: var(--blueraquet-color);
            margin-top: 0;
        }

        .current-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 1rem;
            border: 1px solid var(--border-color);
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <div class="welcome-message">
            <h1>Configuración del Sitio</h1>
            <p>Administra las imágenes y otros ajustes generales del sitio web.</p>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('configuracion.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="config-grid">
                <div class="config-card">
                    <h3>Horarios de Zumba</h3>
                    <p>Sube una nueva imagen para reemplazar la actual. Se guardará como `horarios_zumba.jpg`.</p>
                    <div class="form-group">
                        <label for="horarios_zumba">Seleccionar nueva imagen (JPG, PNG)</label>
                        <input type="file" name="horarios_zumba" id="horarios_zumba" class="form-control">
                    </div>
                    <h4>Imagen Actual:</h4>
                    <img src="{{ asset('image/horarios_zumba.jpg') }}?v={{ time() }}" alt="Horarios de Zumba"
                        class="current-image">
                </div>

                <div class="config-card">
                    <h3>QR de Pago del Club</h3>
                    <p>Sube una nueva imagen para el código QR de pagos. Se guardará como `qr_pago_club.jpg`.</p>
                    <div class="form-group">
                        <label for="qr_pago_club">Seleccionar nueva imagen (JPG, PNG)</label>
                        <input type="file" name="qr_pago_club" id="qr_pago_club" class="form-control">
                    </div>
                    <h4>Imagen Actual:</h4>
                    <img src="{{ asset('image/qr_pago_club.png') }}?v={{ time() }}" alt="QR de Pago"
                        class="current-image">
                </div>
            </div>

            <div class="form-actions" style="margin-top: 2rem;">
                <button type="submit" class="button">Guardar Cambios</button>
            </div>
        </form>
    </div>
@endsection
