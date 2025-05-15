<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - @yield('title', 'Administración')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/style.css') }}"> {{-- Para tu CSS personalizado --}}

    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
            font-family: 'Figtree', sans-serif;
            background-color: #f3f4f6;
        }

        .admin-wrapper {
            display: flex;
            flex: 1;
        }

        .admin-main-content {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
        }

        /* Estilos básicos para header y sidebar (ajusta según tus necesidades) */
        .admin-header {
            background-color: #1f2937;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
        }

        .admin-sidebar {
            background-color: #374151;
            color: white;
            width: 250px;
            padding: 20px;
            min-height: calc(100vh -
                    /*altura del header*/
                );
        }

        /* Ajusta la altura si el header es fijo */
        .admin-sidebar ul {
            list-style: none;
            padding: 0;
        }

        .admin-sidebar ul li a {
            color: #d1d5db;
            text-decoration: none;
            display: block;
            padding: 10px 15px;
            border-radius: 4px;
        }

        .admin-sidebar ul li a:hover {
            background-color: #4b5563;
            color: white;
        }
    </style>
    @stack('styles')
</head>

<body>
    <x-admin.header />

    <div class="admin-wrapper">
        {{-- <x-admin.sidebar /> --}}

        <main class="admin-main-content">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>

</html>
