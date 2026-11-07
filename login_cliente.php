
<?php
session_start();
require_once "config/conexion.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $correo = trim($_POST["correo"] ?? "");
    $contrasena = $_POST["contrasena"] ?? "";

    $consulta = $conexion->prepare(
        "SELECT id, nombre, correo, direccion, contrasena
         FROM clientes
         WHERE correo = ?"
    );

    $consulta->bind_param("s", $correo);
    $consulta->execute();

    $resultado = $consulta->get_result();
    $cliente = $resultado->fetch_assoc();

    if (
        $cliente &&
        !empty($cliente["contrasena"]) &&
        password_verify($contrasena, $cliente["contrasena"])
    ) {

        session_regenerate_id(true);

        $_SESSION["cliente_id"] = $cliente["id"];
        $_SESSION["cliente_nombre"] = $cliente["nombre"];
        $_SESSION["cliente_correo"] = $cliente["correo"];
        $_SESSION["cliente_direccion"] = $cliente["direccion"];

        // Recuperar la página a la que quería entrar
        $destino = $_SESSION["destino_despues_login"] ?? "productos.php";

        unset($_SESSION["destino_despues_login"]);

        // Evitar redirecciones externas
        $paginas_permitidas = [
            "productos.php",
            "mis_pedidos.php",
            "comprar.php"
        ];

        $ruta = parse_url($destino, PHP_URL_PATH);
        $pagina = basename($ruta ?: "");

        if (!in_array($pagina, $paginas_permitidas, true)) {
            $destino = "productos.php";
        }

        header("Location: " . $destino);
        exit;

    } else {

        $error = "Correo o contraseña incorrectos.";

    }

    $consulta->close();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión | NOVA MARKET</title>

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
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 12px 35px rgba(48, 37, 143, .10);
        }

        .login-tarjeta h2 {
            text-align: center;
            color: #30258f;
            margin-bottom: 10px;
        }

        .login-descripcion {
            text-align: center;
            color: #777;
            margin-bottom: 28px;
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
            font-size: 15px;
            box-sizing: border-box;
            outline: none;
        }

        .login-formulario input:focus {
            border-color: #5145e5;
            box-shadow: 0 0 0 3px rgba(81, 69, 229, .10);
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
        }

        .login-boton:hover {
            background: #30258f;
        }

        .login-error {
            padding: 12px;
            border-radius: 9px;
            background: #fff0f0;
            color: #c62828;
            text-align: center;
            margin-bottom: 15px;
        }

        .login-enlace {
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
        <a href="registro.php">Crear cuenta</a>
    </nav>

</header>

<main class="login-fondo">

    <section class="login-tarjeta">

        <h2>Iniciar sesión</h2>

        <p class="login-descripcion">
            Ingresa a tu cuenta para continuar comprando.
        </p>

        <?php if ($error !== "") { ?>

            <div class="login-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php } ?>

        <form method="POST" class="login-formulario">

            <label for="correo">Correo electrónico</label>

            <input
                type="email"
                id="correo"
                name="correo"
                placeholder="Tu correo electrónico"
                required
            >

            <label for="contrasena">Contraseña</label>

            <input
                type="password"
                id="contrasena"
                name="contrasena"
                placeholder="Tu contraseña"
                required
            >

            <button type="submit" class="login-boton">
                Iniciar sesión
            </button>

        </form>

        <a href="registro.php" class="login-enlace">
            ¿No tienes cuenta? Crear una cuenta
        </a>

        <a href="index.php" class="login-enlace">
            Volver a la tienda
        </a>

    </section>

</main>

<footer class="pie">
    <p>© 2026 NOVA MARKET. Todos los derechos reservados.</p>
</footer>

</body>
</html>
