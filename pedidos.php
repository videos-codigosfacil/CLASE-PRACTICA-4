
<?php
session_start();

// Verificar administrador
if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/conexion.php";

// Crear token de seguridad
if (empty($_SESSION["token_admin_pedidos"])) {
    $_SESSION["token_admin_pedidos"] = bin2hex(random_bytes(32));
}

$token = $_SESSION["token_admin_pedidos"];

// Mensaje de confirmación
$mensaje = $_SESSION["mensaje_admin_pedidos"] ?? "";
unset($_SESSION["mensaje_admin_pedidos"]);

// Procesar actualización
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id = (int) ($_POST["id_pedido"] ?? 0);
    $estado = $_POST["estado"] ?? "";
    $token_recibido = $_POST["token"] ?? "";

    $estados_permitidos = [
        "Pendiente",
        "Confirmado",
        "Rechazado"
    ];

    if (!hash_equals($token, $token_recibido)) {

        $_SESSION["mensaje_admin_pedidos"] = "La solicitud no es válida.";

    } elseif ($id <= 0 || !in_array($estado, $estados_permitidos, true)) {

        $_SESSION["mensaje_admin_pedidos"] = "Los datos enviados no son válidos.";

    } else {

        $stmt = $conexion->prepare(
            "UPDATE pedidos
             SET estado = ?
             WHERE id = ?
             AND estado != 'Cancelada'"
        );

        $stmt->bind_param("si", $estado, $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $_SESSION["mensaje_admin_pedidos"] = "El estado del pedido se actualizó correctamente.";
        } else {
            $_SESSION["mensaje_admin_pedidos"] = "No se realizaron cambios. Comprueba el estado del pedido.";
        }

        $stmt->close();
    }

    header("Location: pedidos.php");
    exit;
}

// Consultar pedidos
$sql = "SELECT
            pedidos.id,
            clientes.nombre AS cliente,
            clientes.correo,
            productos.nombre AS producto,
            pedidos.fecha,
            pedidos.total,
            pedidos.estado,
            pedidos.metodo_pago,
            pedidos.estado_pago
        FROM pedidos
        INNER JOIN clientes
            ON pedidos.id_cliente = clientes.id
        INNER JOIN productos
            ON pedidos.id_producto = productos.id
        ORDER BY pedidos.fecha DESC";

$resultado = $conexion->query($sql);

if (!$resultado) {
    die("Error al consultar los pedidos: " . $conexion->error);
}

// Estadísticas
$estadisticas = $conexion->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(estado = 'Pendiente'), 0) AS pendientes,
        COALESCE(SUM(estado = 'Confirmado'), 0) AS confirmados,
        COALESCE(SUM(estado = 'Rechazado'), 0) AS rechazados,
        COALESCE(SUM(estado = 'Cancelada'), 0) AS cancelados,
        COALESCE(
            SUM(
                CASE
                    WHEN estado = 'Confirmado'
                    AND estado_pago = 'Aprobado (simulado)'
                    THEN total
                    ELSE 0
                END
            ), 0
        ) AS ventas
    FROM pedidos
");

if (!$estadisticas) {
    die("Error en estadísticas: " . $conexion->error);
}

$datos = $estadisticas->fetch_assoc();

// Clientes registrados
$consulta_clientes = $conexion->query(
    "SELECT COUNT(*) AS total_clientes FROM clientes"
);

if (!$consulta_clientes) {
    die("Error al consultar clientes: " . $conexion->error);
}

$clientes_total = $consulta_clientes->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pedidos | NOVA MARKET</title>

    <link rel="stylesheet" href="css/estilo.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>

        .mensaje-admin {
            padding: 14px 18px;
            margin: 20px 0;
            border-radius: 10px;
            background: #eeeaff;
            color: #30258f;
        }

        .estadisticas {
            margin: 25px 0;
        }

        .tabla-contenedor {
            width: 100%;
            overflow-x: auto;
            margin-top: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,.05);
        }

        .tabla-pedidos {
            width: 100%;
            min-width: 1100px;
            border-collapse: collapse;
        }

        .tabla-pedidos th,
        .tabla-pedidos td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #eeeeF5;
        }

        .tabla-pedidos th {
            background: #5145e5;
            color: white;
        }

        .tabla-pedidos tr:hover {
            background: #f8f7ff;
        }

        .tabla-pedidos select {
            padding: 9px;
            border: 1px solid #ddd;
            border-radius: 7px;
            margin-bottom: 8px;
        }

        .tabla-pedidos button {
            border: none;
            cursor: pointer;
        }

        .grafico-contenedor {
            margin: 35px 0;
            padding: 25px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,.05);
        }

        .grafico {
            position: relative;
            max-width: 500px;
            height: 300px;
            margin: auto;
        }

        .estado-cancelado {
            color: #bd3030;
            font-weight: 700;
        }

        @media (max-width: 650px) {
            .estadisticas {
                gap: 12px;
            }

            .grafico-contenedor {
                padding: 15px;
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
        <a href="pedidos.php">Pedidos</a>
        <a href="admin_productos.php">Administrar productos</a>
        <a href="cerrar_sesion.php">Cerrar sesión</a>
    </nav>

</header>

<main class="contenedor">

    <h2>Administración de pedidos</h2>

    <p>Consulta las compras realizadas y administra sus estados.</p>

    <?php if ($mensaje !== "") { ?>

        <div class="mensaje-admin">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>

    <?php } ?>

    <!-- ESTADÍSTICAS -->

    <div class="estadisticas">

        <div class="estadistica">
            <h3>Total de pedidos</h3>
            <p><?php echo (int) $datos["total"]; ?></p>
        </div>

        <div class="estadistica">
            <h3>Pendientes</h3>
            <p><?php echo (int) $datos["pendientes"]; ?></p>
        </div>

        <div class="estadistica">
            <h3>Confirmados</h3>
            <p><?php echo (int) $datos["confirmados"]; ?></p>
        </div>

        <div class="estadistica">
            <h3>Rechazados</h3>
            <p><?php echo (int) $datos["rechazados"]; ?></p>
        </div>

        <div class="estadistica">
            <h3>Cancelados</h3>
            <p><?php echo (int) $datos["cancelados"]; ?></p>
        </div>

        <div class="estadistica">
            <h3>Ventas pagadas (simuladas)</h3>
            <p>
                $<?php echo number_format((float) $datos["ventas"], 2); ?>
            </p>
        </div>

        <div class="estadistica">
            <h3>Clientes registrados</h3>
            <p><?php echo (int) $clientes_total["total_clientes"]; ?></p>
        </div>

    </div>

    <!-- GRÁFICO -->

    <section class="grafico-contenedor">

        <h2>Resumen de pedidos</h2>

        <div class="grafico">
            <canvas id="graficoPedidos"></canvas>
        </div>

    </section>

    <!-- TABLA -->

    <h2>Pedidos registrados</h2>

    <?php if ($resultado->num_rows > 0) { ?>

        <div class="tabla-contenedor">

            <table class="tabla-pedidos">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Correo</th>
                        <th>Producto</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Método de pago</th>
                        <th>Estado del pago</th>
                        <th>Estado del pedido</th>
                        <th>Actualizar</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while ($pedido = $resultado->fetch_assoc()) { ?>

                        <tr>

                            <td>
                                <?php echo (int) $pedido["id"]; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($pedido["cliente"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($pedido["correo"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($pedido["producto"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($pedido["fecha"]); ?>
                            </td>

                            <td>
                                $<?php echo number_format((float) $pedido["total"], 2); ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $pedido["metodo_pago"] ?? "No especificado"
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $pedido["estado_pago"] ?? "Pendiente"
                                );
                                ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($pedido["estado"]); ?>
                            </td>

                            <td>

                                <?php if ($pedido["estado"] !== "Cancelada") { ?>

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="id_pedido"
                                            value="<?php echo (int) $pedido["id"]; ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="token"
                                            value="<?php echo htmlspecialchars($token); ?>"
                                        >

                                        <select name="estado" required>

                                            <option value="Pendiente"
                                                <?php echo $pedido["estado"] === "Pendiente" ? "selected" : ""; ?>>
                                                Pendiente
                                            </option>

                                            <option value="Confirmado"
                                                <?php echo $pedido["estado"] === "Confirmado" ? "selected" : ""; ?>>
                                                Confirmado
                                            </option>

                                            <option value="Rechazado"
                                                <?php echo $pedido["estado"] === "Rechazado" ? "selected" : ""; ?>>
                                                Rechazado
                                            </option>

                                        </select>

                                        <button type="submit" class="boton">
                                            Guardar
                                        </button>

                                    </form>

                                <?php } else { ?>

                                    <strong class="estado-cancelado">
                                        Pedido cancelado
                                    </strong>

                                <?php } ?>

                            </td>

                        </tr>

                    <?php } ?>

                </tbody>

            </table>

        </div>

    <?php } else { ?>

        <p>Todavía no hay pedidos registrados.</p>

    <?php } ?>

</main>

<footer class="pie">
    <p>© 2026 NOVA MARKET. Todos los derechos reservados.</p>
</footer>

<script>

const contexto = document.getElementById("graficoPedidos");

new Chart(contexto, {

    type: "doughnut",

    data: {

        labels: [
            "Pendientes",
            "Confirmados",
            "Rechazados",
            "Cancelados"
        ],

        datasets: [{

            data: [
                <?php echo (int) $datos["pendientes"]; ?>,
                <?php echo (int) $datos["confirmados"]; ?>,
                <?php echo (int) $datos["rechazados"]; ?>,
                <?php echo (int) $datos["cancelados"]; ?>
            ],

            backgroundColor: [
                "#f5b942",
                "#35b779",
                "#e45b65",
                "#8875ff"
            ],

            borderWidth: 2,
            borderColor: "#ffffff"

        }]

    },

    options: {

        responsive: true,
        maintainAspectRatio: false,

        plugins: {
            legend: {
                position: "bottom"
            }
        }

    }

});

</script>

</body>
</html>
