<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">

</head>

<body>
    <div class="container">
        <div class="info">
            <h1>Login</h1>
        </div>
    </div>

    <div class="form">
        <div class="thumbnail">
            <img class="logo"
                src="https://png.pngtree.com/png-clipart/20220826/ourmid/pngtree-volleyball-player-red-custom-png-image_6124936.png"
                alt="Graduation Hat">
        </div>

        <form class="register-form">
            <input type="text" placeholder="name">
            <input type="password" placeholder="password">
            <input type="text" placeholder="email address">
            <button>create</button>
            <p class="message">ya tiene una cuenta? <a href="#">Iniciar sesion</a></p>
        </form>

        <form class="login-form">
            <input type="text" placeholder="username">
            <input type="password" placeholder="password">
            <button>login</button>
            <p class="message">no tiene una cuenta?<a href="#">Crear cuenta</a></p>
        </form>
    </div>



    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        $('.message a').click(function() {
            $('form').animate({
                height: "toggle",
                opacity: "toggle"
            }, "slow");
        });
    </script>
</body>

</html>
