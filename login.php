
<?php
session_start();
require_once "config/conexion.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario = trim($_POST["usuario"] ?? "");
    $contrasena = $_POST["contrasena"] ?? "";

    $consulta = $conexion->prepare(
        "SELECT id, usuario, contrasena
         FROM administradores
         WHERE usuario = ?"
    );

    $consulta->bind_param("s", $usuario);
    $consulta->execute();

    $resultado = $consulta->get_result();
    $admin = $resultado->fetch_assoc();

    if ($admin && password_verify($contrasena, $admin["contrasena"])) {
        session_regenerate_id(true);

        $_SESSION["admin_id"] = $admin["id"];
        $_SESSION["admin_usuario"] = $admin["usuario"];

        header("Location: pedidos.php");
        exit;
    }

    $error = "Usuario o contraseña incorrectos.";
    $consulta->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración | NOVA MARKET</title>
    <link rel="stylesheet" href="css/estilo.css">

    <style>
        .login-fondo {
            min-height: 75vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 45px 20px;
            background: #f5f5fc;
        }

        .login-tarjeta {
            width: 100%;
            max-width: 430px;
            background: #ffffff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 12px 35px rgba(48, 37, 143, 0.10);
        }

        .login-icono {
            width: 65px;
            height: 65px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border-radius: 18px;
            background: #eeeaff;
            color: #5145e5;
            font-size: 30px;
        }

        .login-tarjeta h2 {
            text-align: center;
            color: #30258f;
            margin-bottom: 10px;
        }

        .login-descripcion {
            text-align: center;
            color: #777;
            margin-bottom: 30px;
        }

        .login-formulario {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .login-formulario label {
            font-weight: 600;
            color: #333;
            margin-top: 8px;
        }

        .login-formulario input {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid #dedee9;
            border-radius: 10px;
            outline: none;
            font-size: 15px;
            box-sizing: border-box;
        }

        .login-formulario input:focus {
            border-color: #5145e5;
            box-shadow: 0 0 0 3px rgba(81, 69, 229, 0.10);
        }

        .login-boton {
            margin-top: 15px;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: #5145e5;
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }

        .login-boton:hover {
            background: #30258f;
            transform: translateY(-2px);
        }

        .login-error {
            padding: 12px;
            border-radius: 9px;
            background: #fff0f0;
            color: #c62828;
            text-align: center;
            font-size: 14px;
        }

        .login-regreso {
            display: block;
            margin-top: 22px;
            text-align: center;
            color: #5145e5;
            text-decoration: none;
            font-weight: 600;
        }

        .login-regreso:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

<header class="encabezado">
    <h1>NOVA MARKET</h1>
    <nav>
        <a href="index.php">Inicio</a>
        <a href="productos.php">Productos</a>
    </nav>
</header>

<main class="login-fondo">

    <section class="login-tarjeta">

        <div class="login-icono">♙</div>

        <h2>Bienvenido</h2>

        <p class="login-descripcion">
            Ingresa tus datos para administrar NOVA MARKET.
        </p>

        <?php if ($error !== "") { ?>
            <div class="login-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php } ?>

        <form method="POST" class="login-formulario">

            <label for="usuario">Usuario</label>
            <input
                type="text"
                id="usuario"
                name="usuario"
                placeholder="Ingresa tu usuario"
                required
            >

            <label for="contrasena">Contraseña</label>
            <input
                type="password"
                id="contrasena"
                name="contrasena"
                placeholder="Ingresa tu contraseña"
                required
            >

            <button type="submit" class="login-boton">
                Iniciar sesión
            </button>

        </form>

        <a href="index.php" class="login-regreso">
            Volver a la tienda
        </a>

    </section>

</main>

<footer class="pie">
    <p>© 2026 NOVA MARKET. Todos los derechos reservados.</p>
</footer>

</body>
</html>
