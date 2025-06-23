@extends('layouts.app')

@section('content')

    <div class="page-container">
        
        <header class="page-header">
            <div class="header-info">
                <h1><i class="fas fa-users-cog me-2"></i>Gestión de Empleados</h1>
            </div>
            <a href="{{ route('empleados.create') }}" class="btn-primary btn-new-employee">
                <i class="fas fa-plus-circle me-2"></i>Nuevo Empleado
            </a>
        </header>
        <!-- Buscador por teléfono -->
<form method="GET" action="{{ route('empleados.index') }}" style="margin: 20px 0; display: flex; gap: 10px; align-items: center;">
    <input type="text" name="telefono" placeholder="Buscar por teléfono..."
           value="{{ request('telefono') }}"
           style="padding: 10px; background: #333; color: #fff; border: 1px solidrgb(82, 189, 231); border-radius: 5px; width: 250px;">
    <button type="submit"
            style="padding: 10px 20px; background:rgb(119, 222, 253); color: #fff; border: none; border-radius: 5px; font-weight: bold;">
        Buscar
    </button>
</form>

        

        <section class="card">
            <div class="card-header">
                <h2><i class="fas fa-list-ol me-2"></i>Listado Completo</h2>
            </div>

            @if(session('success'))
                <div class="alert-success">
                    <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
                    <button type="button" class="close-alert" aria-label="Cerrar">&times;</button>
                </div>
            @endif

            <div class="table-container">
                <table class="employees-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($empleados as $empleado)
                        <tr>
                            <td class="employee-name">
                                <span class="avatar"><i class="fas fa-user"></i></span>
                                <div>
                                    <div class="name-text">{{ $empleado->nombre }}</div>
                                    <small class="employee-id">ID: {{ $empleado->empleado_id }}</small>
                                </div>
                            </td>
                            <td>{{ $empleado->usuario }}</td>
                            <td><a href="mailto:{{ $empleado->email }}" class="link-primary">{{ $empleado->email }}</a></td>
                            <td><a href="tel:{{ $empleado->telefono }}" class="link-primary">{{ $empleado->telefono }}</a></td>
                            <td>
                                <span class="badge badge-role {{ $empleado->rol == 'admin' ? 'admin' : 'user' }}">
                                    {{ ucfirst($empleado->rol) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-status {{ $empleado->activo ? 'active' : 'inactive' }}">
                                    <i class="fas fa-{{ $empleado->activo ? 'check-circle' : 'times-circle' }}"></i>
                                    {{ $empleado->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="actions">
                                <a href="{{ route('empleados.edit', $empleado->empleado_id) }}" class="btn-action edit" title="Editar" data-bs-toggle="tooltip">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form action="{{ route('empleados.destroy', $empleado->empleado_id) }}" method="POST" onsubmit="return confirm('¿Confirmas que deseas eliminar este empleado?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-action delete" title="Eliminar" data-bs-toggle="tooltip">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="empty-state">
                                <i class="fas fa-user-slash"></i>
                                <h3>No se encontraron empleados</h3>
                                <p>Agrega nuevos empleados para comenzar</p>
                                <a href="{{ route('empleados.create') }}" class="btn-primary btn-create-first">
                                    <i class="fas fa-plus"></i> Crear Primer Empleado
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <footer class="card-footer">
                Mostrando <strong>{{ $empleados->count() }}</strong> registro{{ $empleados->count() !== 1 ? 's' : '' }}
            </footer>
        </section>
    </div>
</div>

@push('styles')
<link href="{{ asset('css/styleEmpleados.css') }}" rel="stylesheet">
@endpush


@push('scripts')
<script>
    // Activar tooltips y cerrar alerts
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Alert close buttons
        document.querySelectorAll('.close-alert').forEach(function(button) {
            button.addEventListener('click', function() {
                this.parentElement.style.display = 'none';
            });
        });
    })
</script>
@endpush

@endsection

