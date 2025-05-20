@extends('layouts.app')

@section('content')
<div class="page-wrapper">
    <div class="page-container">
        <header class="page-header">
            <div class="header-info">
                <h1><i class="fas fa-users-cog me-2"></i>Gestión de Empleados</h1>
                <p class="subtitle">Administra el personal de tu organización con facilidad</p>
            </div>
            <a href="{{ route('empleados.create') }}" class="btn-primary btn-new-employee">
                <i class="fas fa-plus-circle me-2"></i>Nuevo Empleado
            </a>
        </header>

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
<style>
    /* Reset some bootstrap defaults */
    .table thead th {
        border-bottom: 2px solid #dee2e6;
    }
    /* Page wrapper container */
    .page-wrapper {
        background: linear-gradient(135deg, #f0f4f8 0%, #d9e9f9 100%);
        min-height: 100vh;
        padding: 50px 20px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        display: flex;
        justify-content: center;
    }
    .page-container {
        background: #fff;
        max-width: 1100px;
        width: 100%;
        border-radius: 12px;
        box-shadow: 0 10px 35px rgba(0,0,0,0.1);
        padding: 30px 40px;
    }
    /* Header styles */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 35px;
        flex-wrap: wrap;
        gap: 15px;
    }
    .header-info h1 {
        color: #0056b3;
        font-size: 2.5rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .header-info .subtitle {
        font-size: 1.1rem;
        color: #6c757d;
        margin-top: 6px;
    }
    .btn-new-employee {
        background-color: #198754;
        color: #fff;
        border-radius: 40px;
        padding: 12px 28px;
        font-weight: 600;
        font-size: 1.1rem;
        box-shadow: 0 6px 15px rgba(25, 135, 84, 0.5);
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
        border: none;
        display: flex;
        align-items: center;
    }
    .btn-new-employee:hover,
    .btn-new-employee:focus {
        background-color: #146c43;
        box-shadow: 0 8px 18px rgba(20,108,67,0.7);
        color: white;
        text-decoration: none;
    }
    /* Card styles */
    .card {
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.05);
        background-color: #fff;
        display: flex;
        flex-direction: column;
    }
    .card-header {
        background: #0056b3;
        padding: 20px 30px;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        color: #fff;
        font-size: 1.7rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .card-header i {
        font-size: 1.8rem;
    }
    /* Alert Success */
    .alert-success {
        padding: 15px 20px;
        background-color: #d1e7dd;
        color: #0f5132;
        border-radius: 8px;
        margin: 20px 30px;
        position: relative;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 2px 8px rgba(15,81,50,0.2);
    }
    .alert-success i {
        font-size: 1.3rem;
    }
    .close-alert {
        cursor: pointer;
        border: none;
        background: transparent;
        font-size: 1.4rem;
        position: absolute;
        top: 10px;
        right: 15px;
        color: #0f5132;
        font-weight: 700;
    }
    /* Table container */
    .table-container {
        overflow-x: auto;
        padding: 0 30px 30px 30px;
    }
    .employees-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
        font-size: 1rem;
        color: #212529;
        min-width: 900px;
    }
    .employees-table thead tr th {
        padding: 14px 20px;
        text-align: left;
        color: #495057;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: none;
    }
    .employees-table tbody tr {
        background-color: #f8f9fa;
        border-radius: 10px;
        transition: box-shadow 0.3s ease;
        box-shadow: 0 1px 4px rgb(0 0 0 / 0.1);
    }
    .employees-table tbody tr:hover {
        box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        background-color: #e9ecef;
    }
    .employees-table tbody tr td {
        padding: 15px 20px;
        vertical-align: middle;
    }
    /* Avatar and name */
    .employee-name {
        display: flex;
        align-items: center;
        gap: 15px;
        font-weight: 600;
        color: #343a40;
    }
    .employee-name .avatar {
        width: 42px;
        height: 42px;
        background-color: #b3d7ff;
        color: #0056b3;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
    }
    .name-text {
        font-weight: 600;
    }
    .employee-id {
        font-size: 0.8rem;
        color: #6c757d;
    }
    /* Links */
    .link-primary {
        color: #0d6efd;
        text-decoration: none;
        font-weight: 500;
    }
    .link-primary:hover {
        text-decoration: underline;
    }
    /* Badges */
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
        user-select: none;
        white-space: nowrap;
    }
    .badge-role.admin {
        background-color: #d6336c;
    }
    .badge-role.user {
        background-color: #339af0;
    }
    .badge-status.active {
        background-color: #198754;
    }
    .badge-status.inactive {
        background-color: #6c757d;
    }
    .badge i {
        font-size: 1rem;
    }
    /* Actions */
    .actions {
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    .btn-action {
        border: none;
        background: transparent;
        cursor: pointer;
        font-size: 1.1rem;
        padding: 6px 10px;
        border-radius: 50%;
        transition: background-color 0.25s ease;
        color: #495057;
    }
    .btn-action.edit:hover {
        background-color: #ffc107;
        color: #212529;
    }
    .btn-action.delete:hover {
        background-color: #dc3545;
        color: white;
    }
    .btn-action i {
        pointer-events: none;
    }
    .btn-action:focus {
        outline: 2px solid #0d6efd;
        outline-offset: 2px;
    }
    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 50px 0;
        color: #6c757d;
    }
    .empty-state i {
        font-size: 3.5rem;
        margin-bottom: 20px;
        display: inline-block;
        color: #adb5bd;
    }
    .empty-state h3 {
        font-weight: 700;
        font-size: 1.8rem;
        margin-bottom: 10px;
    }
    .empty-state p {
        font-size: 1rem;
        margin-bottom: 25px;
    }
    .btn-create-first {
        padding: 12px 28px;
        background-color: #0d6efd;
        color: white;
        font-weight: 600;
        border-radius: 40px;
        font-size: 1rem;
        box-shadow: 0 6px 15px rgba(13,110,253,0.5);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        justify-content: center;
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
    }
    .btn-create-first:hover,
    .btn-create-first:focus {
        background-color: #084298;
        box-shadow: 0 8px 18px rgba(8, 66, 152, 0.7);
    }
    /* Card Footer */
    .card-footer {
        padding: 15px 30px;
        border-top: 1px solid #dee2e6;
        font-size: 0.9rem;
        color: #6c757d;
        font-weight: 500;
    }
</style>
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

