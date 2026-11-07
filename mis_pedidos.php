
<?php
session_start();
require_once "config/conexion.php";

// Verificar que el cliente haya iniciado sesión
if (!isset($_SESSION["cliente_id"])) {
    header("Location: login_cliente.php");
    exit;
}

$id_cliente = (int) $_SESSION["cliente_id"];
$mensaje = "";

// Crear token de seguridad
if (empty($_SESSION["token_pedidos"])) {
    $_SESSION["token_pedidos"] = bin2hex(random_bytes(32));
}

// Procesar cancelación
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id_pedido = (int) ($_POST["id_pedido"] ?? 0);
    $token = $_POST["token"] ?? "";

    if (!hash_equals($_SESSION["token_pedidos"], $token)) {
        $mensaje = "La solicitud no es válida.";
    } else {
        $stmt = $conexion->prepare(
            "UPDATE pedidos
             SET estado = 'Cancelada'
             WHERE id = ?
             AND id_cliente = ?
             AND estado = 'Pendiente'"
        );

        $stmt->bind_param("ii", $id_pedido, $id_cliente);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $mensaje = "Tu pedido fue cancelado correctamente.";
        } else {
            $mensaje = "No se pudo cancelar. El pedido ya fue procesado o no te pertenece.";
        }

        $stmt->close();
    }
}

// Consultar pedidos del cliente
$stmt = $conexion->prepare(
    "SELECT
        pedidos.id,
        productos.nombre AS producto,
        productos.imagen,
        pedidos.fecha,
        pedidos.total,
        pedidos.estado
     FROM pedidos
     INNER JOIN productos
        ON pedidos.id_producto = productos.id
     WHERE pedidos.id_cliente = ?
     ORDER BY pedidos.fecha DESC"
);

$stmt->bind_param("i", $id_cliente);
$stmt->execute();

$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis pedidos | NOVA MARKET</title>
    <link rel="stylesheet" href="css/estilo.css">

    <style>
        .pedidos-contenedor {
            max-width: 1100px;
            margin: 45px auto;
            padding: 0 20px;
        }

        .pedido-titulo {
            margin-bottom: 25px;
        }

        .pedido-titulo h2 {
            color: #30258f;
        }

        .pedido-tarjeta {
            display: flex;
            align-items: center;
            gap: 20px;
            background: white;
            padding: 22px;
            margin-bottom: 18px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,.06);
        }

        .pedido-tarjeta img {
            width: 115px;
            height: 115px;
            object-fit: contain;
            border-radius: 10px;
            background: #f7f7fb;
        }

        .pedido-info {
            flex: 1;
        }

        .pedido-info h3 {
            margin: 0 0 10px;
            color: #30258f;
        }

        .pedido-estado {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            background: #fff2d9;
            color: #946200;
            font-size: 13px;
            font-weight: 700;
        }

        .pedido-estado.confirmado {
            background: #e4f7eb;
            color: #237443;
        }

        .pedido-estado.cancelado,
        .pedido-estado.rechazado {
            background: #ffe9e9;
            color: #bd3030;
        }

        .boton-cancelar {
            padding: 11px 16px;
            border: none;
            border-radius: 9px;
            background: #e74c3c;
            color: white;
            font-weight: 600;
            cursor: pointer;
        }

        .boton-cancelar:hover {
            background: #c0392b;
        }

        .mensaje-pedidos {
            padding: 13px;
            margin-bottom: 20px;
            border-radius: 10px;
            background: #eeeaff;
            color: #30258f;
        }

        .sin-pedidos {
            padding: 35px;
            text-align: center;
            background: white;
            border-radius: 15px;
        }

        @media (max-width: 650px) {
            .pedido-tarjeta {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<header class="encabezado">
    <h1>NOVA MARKET</h1>

    <nav>
        <a href="index.php">Inicio</a>
        <a href="productos.php">Productos</a>
        <a href="mis_pedidos.php">Mis pedidos</a>
        <a href="cerrar_sesion_cliente.php">Cerrar sesión</a>
    </nav>
</header>

<main class="pedidos-contenedor">

    <div class="pedido-titulo">
        <h2>Mis pedidos</h2>
        <p>Consulta tus compras y revisa su estado.</p>
    </div>

    <?php if ($mensaje !== "") { ?>
        <div class="mensaje-pedidos">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php } ?>

    <?php if ($resultado->num_rows > 0) { ?>

        <?php while ($pedido = $resultado->fetch_assoc()) { ?>

            <?php
            $estado_clase = "";

            if ($pedido["estado"] === "Confirmado") {
                $estado_clase = "confirmado";
            } elseif (
                $pedido["estado"] === "Cancelada" ||
                $pedido["estado"] === "Rechazado"
            ) {
                $estado_clase = "cancelado";
            }
            ?>

            <article class="pedido-tarjeta">

                <img
                    src="imagenes/<?php echo htmlspecialchars($pedido["imagen"]); ?>"
                    alt="<?php echo htmlspecialchars($pedido["producto"]); ?>"
                >

                <div class="pedido-info">

                    <h3>
                        <?php echo htmlspecialchars($pedido["producto"]); ?>
                    </h3>

                    <p>
                        Pedido número: <?php echo $pedido["id"]; ?>
                    </p>

                    <p>
                        Fecha: <?php echo htmlspecialchars($pedido["fecha"]); ?>
                    </p>

                    <p>
                        Total: $<?php echo number_format($pedido["total"], 2); ?>
                    </p>

                    <span class="pedido-estado <?php echo $estado_clase; ?>">
                        <?php echo htmlspecialchars($pedido["estado"]); ?>
                    </span>

                </div>

                <?php if ($pedido["estado"] === "Pendiente") { ?>

                    <form method="POST"
                          onsubmit="return confirm('¿Estás seguro de cancelar este pedido?');">

                        <input type="hidden" name="id_pedido"
                               value="<?php echo $pedido["id"]; ?>">

                        <input type="hidden" name="token"
                               value="<?php echo htmlspecialchars($_SESSION["token_pedidos"]); ?>">

                        <button type="submit" class="boton-cancelar">
                            Cancelar pedido
                        </button>

                    </form>

                <?php } ?>

            </article>

        <?php } ?>

    <?php } else { ?>

        <div class="sin-pedidos">
            <h3>Aún no tienes pedidos</h3>
            <p>Cuando realices una compra, aparecerá aquí.</p>
            <a href="productos.php" class="boton">
                Explorar productos
            </a>
        </div>

    <?php } ?>

</main>

<footer class="pie">
    <p>© 2026 NOVA MARKET. Todos los derechos reservados.</p>
</footer>

</body>
</html>
