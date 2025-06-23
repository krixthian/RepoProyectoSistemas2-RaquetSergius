@extends('layouts.app')

@section('title', 'Crear Nuevo Instructor')

@push('styles')
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
            <h1>Crear Nuevo Instructor</h1>
        </div>

        <form action="{{ route('instructores.store') }}" method="POST">
            @csrf
            @include('instructores.form', ['instructor' => null])
            <div class="form-actions" style="margin-top: 2rem;">
                <button type="submit" class="button">Guardar Instructor</button>
                <a href="{{ route('instructores.index') }}" class="button" style="background-color: #6c757d;">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
