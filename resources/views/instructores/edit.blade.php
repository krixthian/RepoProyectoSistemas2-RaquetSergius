@extends('layouts.app')

@section('title', 'Editar Instructor')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style>
        .form-card {
            background-color: var(--surface-color);
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid var(--border-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <div class="welcome-message">
            <h1>Editar Instructor: {{ $instructor->nombre }}</h1>
        </div>

        {{-- CORRECCIÓN EN LA LÍNEA SIGUIENTE --}}
        <form action="{{ route('instructores.update', ['instructore' => $instructor]) }}" method="POST">
            @csrf
            @method('PUT')
            @include('instructores.form')
            <div class="form-actions" style="margin-top: 2rem;">
                <button type="submit" class="button">Actualizar Instructor</button>
                <a href="{{ route('instructores.index') }}" class="button" style="background-color: #6c757d;">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
