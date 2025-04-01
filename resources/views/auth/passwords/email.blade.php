<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #1E293B;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container-box {
            background: #FFFFFF;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }

        h2 {
            color: #1E293B;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #94A3B8;
        }

        .btn-custom {
            background-color: #1E293B;
            color: #FFFFFF;
            border: none;
            padding: 12px;
            border-radius: 8px;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-custom:hover {
            background-color: #334155;
        }

        .btn-secondary {
            background-color: #94A3B8;
            color: #1E293B;
            border: none;
            padding: 10px;
            border-radius: 8px;
            width: 100%;
            margin-top: 10px;
        }

        .btn-secondary:hover {
            background-color: #CBD5E1;
        }

        .alert-success {
            background-color: #D1FAE5;
            border-color: #10B981;
            color: #065F46;
        }
    </style>
</head>
<body>
    <div class="container-box">
        <h2>Recuperar contraseña</h2>
        @if(session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-custom">Enviar enlace de restablecimiento</button>
        </form>
        <a href="{{ route('login') }}" class="btn btn-secondary">Volver</a>
    </div>
</body>
</html>
