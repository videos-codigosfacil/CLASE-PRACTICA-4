
<?php
session_start();
require_once "config/conexion.php";

// Comprobar inicio de sesión
if (!isset($_SESSION["cliente_id"])) {

    $id_producto = isset($_GET["id"])
        ? (int) $_GET["id"]
        : (int) ($_POST["id_producto"] ?? 0);

    $_SESSION["destino_despues_login"] = "comprar.php?id=" . $id_producto;

    header("Location: login_cliente.php");
    exit;
}

$mensaje = "";
$compra_exitosa = false;

$id_producto = isset($_GET["id"])
    ? (int) $_GET["id"]
    : (int) ($_POST["id_producto"] ?? 0);

$id_cliente = (int) $_SESSION["cliente_id"];

$nombre = $_SESSION["cliente_nombre"];
$correo = $_SESSION["cliente_correo"];
$direccion = $_SESSION["cliente_direccion"];

// Buscar producto
$stmt = $conexion->prepare(
    "SELECT id, nombre, descripcion, precio, imagen
     FROM productos
     WHERE id = ?"
);

$stmt->bind_param("i", $id_producto);
$stmt->execute();

$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$producto) {
    die("El producto que buscas no existe.");
}

// Procesar compra
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $metodo_pago = $_POST["metodo_pago"] ?? "";

    if (!in_array($metodo_pago, ["Tarjeta simulada", "Efectivo"], true)) {
        $mensaje = "Selecciona un método de pago válido.";
    } else {

        try {
            $conexion->begin_transaction();

            $total = (float) $producto["precio"];

            // La tarjeta se simula; el efectivo queda pendiente.
            $estado_pago = ($metodo_pago === "Tarjeta simulada")
                ? "Aprobado (simulado)"
                : "Pendiente";

            $estado_pedido = ($metodo_pago === "Tarjeta simulada")
                ? "Confirmado"
                : "Pendiente";

            $stmt = $conexion->prepare(
                "INSERT INTO pedidos
                (id_cliente, id_producto, total, estado, metodo_pago, estado_pago)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "iidsss",
                $id_cliente,
                $id_producto,
                $total,
                $estado_pedido,
                $metodo_pago,
                $estado_pago
            );

            $stmt->execute();
            $stmt->close();

            $conexion->commit();

            $compra_exitosa = true;
            $mensaje = "¡Tu pedido se registró correctamente!";

        } catch (Throwable $e) {

            $conexion->rollback();

            $mensaje = "No se pudo registrar el pedido. Inténtalo nuevamente.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Comprar | NOVA MARKET</title>

    <link rel="stylesheet" href="css/estilo.css">
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

<main class="compra-contenedor">

    <div class="compra-titulo">
        <span class="etiqueta-compra">NOVA MARKET</span>
        <h2>Finaliza tu pedido</h2>
        <p>Revisa tus datos y selecciona cómo deseas pagar.</p>
    </div>

    <?php if ($mensaje !== "") { ?>
        <div class="mensaje-compra <?php echo $compra_exitosa ? 'exito' : 'error'; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php } ?>

    <?php if (!$compra_exitosa) { ?>

        <div class="compra-panel">

            <section class="producto-resumen">

                <img
                    src="imagenes/<?php echo htmlspecialchars($producto['imagen']); ?>"
                    alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                >

                <div>
                    <span class="resumen-etiqueta">PRODUCTO SELECCIONADO</span>

                    <h3><?php echo htmlspecialchars($producto["nombre"]); ?></h3>

                    <p><?php echo htmlspecialchars($producto["descripcion"]); ?></p>

                    <strong>
                        $<?php echo number_format($producto["precio"], 2); ?>
                    </strong>
                </div>

            </section>

            <section class="formulario-compra">

                <h3>Datos del cliente</h3>

                <form method="POST" action="comprar.php?id=<?php echo $id_producto; ?>">

                    <input
                        type="hidden"
                        name="id_producto"
                        value="<?php echo $id_producto; ?>"
                    >

                    <label>Nombre completo</label>
                    <input
                        type="text"
                        value="<?php echo htmlspecialchars($nombre); ?>"
                        readonly
                    >

                    <label>Correo electrónico</label>
                    <input
                        type="email"
                        value="<?php echo htmlspecialchars($correo); ?>"
                        readonly
                    >

                    <label>Dirección</label>
                    <textarea readonly><?php echo htmlspecialchars($direccion); ?></textarea>

                    <h3 class="titulo-pago">Método de pago</h3>

                    <label class="opcion-pago">
                        <input
                            type="radio"
                            name="metodo_pago"
                            value="Tarjeta simulada"
                            required
                        >
                        <span>
                            <strong>Tarjeta de crédito o débito</strong>
                            <small>Simulación de pago con tarjeta</small>
                        </span>
                        <span>💳</span>
                    </label>

                    <label class="opcion-pago">
                        <input
                            type="radio"
                            name="metodo_pago"
                            value="Efectivo"
                            required
                        >
                        <span>
                            <strong>Pago en efectivo</strong>
                            <small>Pago pendiente de entrega</small>
                        </span>
                        <span>💵</span>
                    </label>

                    <div class="aviso-pago">
                        Los pagos con tarjeta son únicamente de demostración.
                        No se realizan cobros reales ni se solicitan datos bancarios.
                    </div>

                    <button type="submit" class="boton-confirmar">
                        Confirmar pedido
                    </button>

                </form>

            </section>

        </div>

    <?php } else { ?>

        <div class="compra-finalizada">

            <div class="icono-exito">✓</div>

            <h2>¡Pedido registrado!</h2>

            <p>
                Gracias por comprar en NOVA MARKET,
                <?php echo htmlspecialchars($nombre); ?>.
            </p>

            <?php if ($metodo_pago === "Tarjeta simulada") { ?>

                <p>
                    Tu pago de demostración fue aprobado correctamente.
                </p>

                <strong>Estado del pago: Aprobado (simulado)</strong>

            <?php } else { ?>

                <p>
                    Seleccionaste pago en efectivo.
                    El pago queda pendiente.
                </p>

            <?php } ?>

            <p>
                Total: $<?php echo number_format($producto["precio"], 2); ?>
            </p>

            <a href="mis_pedidos.php" class="boton-confirmar">
                Ver mis pedidos
            </a>

        </div>

    <?php } ?>

</main>

<footer class="pie">
    <p>© 2026 NOVA MARKET. Todos los derechos reservados.</p>
</footer>

</body>
</html>
