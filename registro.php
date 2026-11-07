
<?php
require_once "config/conexion.php";

$error = "";
$correcto = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $direccion = trim($_POST["direccion"] ?? "");
    $contrasena = $_POST["contrasena"] ?? "";
    $confirmar = $_POST["confirmar"] ?? "";

    if ($nombre === "" || $correo === "" || $direccion === "" ||
        $contrasena === "" || $confirmar === "") {
        $error = "Completa todos los campos.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "Ingresa un correo electrónico válido.";
    } elseif (strlen($contrasena) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($contrasena !== $confirmar) {
        $error = "Las contraseñas no coinciden.";
    } else {
        $consulta = $conexion->prepare(
            "SELECT id FROM clientes WHERE correo = ?"
        );
        $consulta->bind_param("s", $correo);
        $consulta->execute();

        $resultado = $consulta->get_result();

        if ($resultado->num_rows > 0) {
            $error = "Este correo ya está registrado.";
        } else {
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);

            $insertar = $conexion->prepare(
                "INSERT INTO clientes (nombre, correo, direccion, contrasena)
                 VALUES (?, ?, ?, ?)"
            );

            $insertar->bind_param(
                "ssss",
                $nombre,
                $correo,
                $direccion,
                $hash
            );

            if ($insertar->execute()) {
                $correcto = "¡Tu cuenta fue creada correctamente! Ya puedes iniciar sesión.";
            } else {
                $error = "No se pudo crear la cuenta. Inténtalo nuevamente.";
            }

            $insertar->close();
        }

        $consulta->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta | NOVA MARKET</title>
    <link rel="stylesheet" href="css/estilo.css">

    <style>
        .registro-fondo {
            min-height: 75vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 45px 20px;
            background: #f5f5fc;
        }

        .registro-tarjeta {
            width: 100%;
            max-width: 480px;
            padding: 38px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 12px 35px rgba(48, 37, 143, .10);
        }

        .registro-tarjeta h2 {
            text-align: center;
            color: #30258f;
            margin-bottom: 10px;
        }

        .registro-descripcion {
            text-align: center;
            color: #777;
            margin-bottom: 25px;
        }

        .registro-formulario {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .registro-formulario label {
            font-weight: 600;
            color: #333;
            margin-top: 5px;
        }

        .registro-formulario input {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid #dedee9;
            border-radius: 10px;
            font-size: 15px;
            box-sizing: border-box;
            outline: none;
        }

        .registro-formulario input:focus {
            border-color: #5145e5;
            box-shadow: 0 0 0 3px rgba(81, 69, 229, .10);
        }

        .registro-boton {
            margin-top: 15px;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: #5145e5;
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }

        .registro-boton:hover {
            background: #30258f;
        }

        .registro-error, .registro-exito {
            padding: 12px;
            border-radius: 9px;
            text-align: center;
            margin-bottom: 15px;
        }

        .registro-error {
            color: #c62828;
            background: #fff0f0;
        }

        .registro-exito {
            color: #237443;
            background: #eaf8ef;
        }

        .registro-enlace {
            display: block;
            text-align: center;
            margin-top: 22px;
            color: #5145e5;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>

<body>

<header class="encabezado">
    <h1>NOVA MARKET</h1>
    <nav>
        <a href="index.php">Inicio</a>
        <a href="productos.php">Productos</a>
        <a href="login.php">Administración</a>
    </nav>
</header>

<main class="registro-fondo">

    <section class="registro-tarjeta">

        <h2>Crear una cuenta</h2>

        <p class="registro-descripcion">
            Regístrate en NOVA MARKET y comienza a comprar.
        </p>

        <?php if ($error !== "") { ?>
            <div class="registro-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php } ?>

        <?php if ($correcto !== "") { ?>
            <div class="registro-exito">
                <?php echo htmlspecialchars($correcto); ?>
                <a href="login_cliente.php">Iniciar sesión</a>
            </div>
        <?php } ?>

        <form method="POST" class="registro-formulario">

            <label for="nombre">Nombre completo</label>
            <input type="text" id="nombre" name="nombre"
                   placeholder="Tu nombre" required>

            <label for="correo">Correo electrónico</label>
            <input type="email" id="correo" name="correo"
                   placeholder="correo@ejemplo.com" required>

            <label for="direccion">Dirección</label>
            <input type="text" id="direccion" name="direccion"
                   placeholder="Tu dirección" required>

            <label for="contrasena">Contraseña</label>
            <input type="password" id="contrasena" name="contrasena"
                   placeholder="Mínimo 6 caracteres" minlength="6" required>

            <label for="confirmar">Confirmar contraseña</label>
            <input type="password" id="confirmar" name="confirmar"
                   placeholder="Repite tu contraseña" required>

            <button type="submit" class="registro-boton">
                Crear cuenta
            </button>

        </form>

        <a href="index.php" class="registro-enlace">
            Volver a la tienda
        </a>

    </section>

</main>

<footer class="pie">
    <p>© 2026 NOVA MARKET. Todos los derechos reservados.</p>
</footer>

</body>
</html>
