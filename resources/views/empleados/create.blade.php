@extends('layouts.app')

@section('content')
<div class="page-wrapper">
    <div class="page-container">
        <header class="page-header">
            <h2 class="text-center" style="color: #007c91;">Registrar Nuevo Empleado</h2>
        </header>

        @if($errors->any())
            <div class="alert alert-danger">
                <strong>Errores:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('empleados.store') }}" class="p-4 rounded" style="background-color: #fffaf0;">
            @csrf
            @include('empleados.form')
        </form>
    </div>
</div>
@endsection