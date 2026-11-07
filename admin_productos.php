
<?php
session_start();
require_once "config/conexion.php";

// Verificar que el administrador haya iniciado sesión
if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$mensaje = "";
$tipo_mensaje = "";

// Crear token de seguridad
if (empty($_SESSION["token_productos"])) {
    $_SESSION["token_productos"] = bin2hex(random_bytes(32));
}

$token = $_SESSION["token_productos"];

// Procesar formularios
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals($token, $_POST["token"] ?? "")) {
        die("Solicitud no válida.");
    }

    $accion = $_POST["accion"] ?? "";

    // AGREGAR PRODUCTO
    if ($accion === "agregar" || $accion === "editar") {

        $id = (int) ($_POST["id"] ?? 0);
        $nombre = trim($_POST["nombre"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");
        $precio = (float) ($_POST["precio"] ?? 0);
        $imagen = trim($_POST["imagen"] ?? "");
        $id_vendedor = (int) ($_POST["id_vendedor"] ?? 0);

        if ($nombre === "" || $precio <= 0 || $imagen === "" || $id_vendedor <= 0) {
            $mensaje = "Completa todos los campos correctamente.";
            $tipo_mensaje = "error";

        } elseif ($accion === "agregar") {

            $consulta = $conexion->prepare(
                "INSERT INTO productos (nombre, descripcion, precio, imagen, id_vendedor)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $consulta->bind_param(
                "ssdsi",
                $nombre,
                $descripcion,
                $precio,
                $imagen,
                $id_vendedor
            );

            if ($consulta->execute()) {
                $mensaje = "Producto agregado correctamente.";
                $tipo_mensaje = "exito";
            } else {
                $mensaje = "No se pudo agregar el producto.";
                $tipo_mensaje = "error";
            }

            $consulta->close();

        } elseif ($accion === "editar" && $id > 0) {

            $consulta = $conexion->prepare(
                "UPDATE productos
                 SET nombre = ?, descripcion = ?, precio = ?, imagen = ?, id_vendedor = ?
                 WHERE id = ?"
            );

            $consulta->bind_param(
                "ssdsii",
                $nombre,
                $descripcion,
                $precio,
                $imagen,
                $id_vendedor,
                $id
            );

            if ($consulta->execute()) {
                $mensaje = "Producto actualizado correctamente.";
                $tipo_mensaje = "exito";
            } else {
                $mensaje = "No se pudo actualizar el producto.";
                $tipo_mensaje = "error";
            }

            $consulta->close();
        }
    }

    // ELIMINAR PRODUCTO
    if ($accion === "eliminar") {

        $id = (int) ($_POST["id"] ?? 0);

        if ($id > 0) {
            $consulta = $conexion->prepare(
                "DELETE FROM productos WHERE id = ?"
            );

            $consulta->bind_param("i", $id);

            if ($consulta->execute() && $consulta->affected_rows > 0) {
                $mensaje = "Producto eliminado correctamente.";
                $tipo_mensaje = "exito";
            } else {
                $mensaje = "No se pudo eliminar. Puede que el producto tenga pedidos asociados.";
                $tipo_mensaje = "error";
            }

            $consulta->close();
        }
    }
}

// Obtener vendedores
$vendedores = $conexion->query(
    "SELECT id, nombre FROM vendedores ORDER BY nombre"
);

// Obtener producto para editar
$producto_editar = null;

if (isset($_GET["editar"])) {
    $id_editar = (int) $_GET["editar"];

    $consulta = $conexion->prepare(
        "SELECT * FROM productos WHERE id = ?"
    );

    $consulta->bind_param("i", $id_editar);
    $consulta->execute();

    $producto_editar = $consulta->get_result()->fetch_assoc();
    $consulta->close();
}

// Obtener todos los productos
$productos = $conexion->query(
    "SELECT productos.*, vendedores.nombre AS vendedor
     FROM productos
     LEFT JOIN vendedores ON productos.id_vendedor = vendedores.id
     ORDER BY productos.id DESC"
);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Administrar productos | NOVA MARKET</title>

    <link rel="stylesheet" href="css/estilo.css">

    <style>
        .admin-contenedor {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .admin-titulo {
            margin-bottom: 25px;
        }

        .admin-titulo h2 {
            color: #30258f;
            margin-bottom: 8px;
        }

        .admin-titulo p {
            color: #777;
        }

        .producto-formulario {
            background: white;
            padding: 28px;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(48, 37, 143, .08);
            margin-bottom: 35px;
        }

        .producto-formulario h3 {
            color: #30258f;
            margin-bottom: 20px;
        }

        .campos {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .campo.completo {
            grid-column: 1 / -1;
        }

        .campo label {
            font-weight: 600;
            color: #333;
        }

        .campo input,
        .campo textarea,
        .campo select {
            width: 100%;
            padding: 12px;
            border: 1px solid #dedee9;
            border-radius: 9px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .campo textarea {
            min-height: 90px;
            resize: vertical;
        }

        .boton {
            display: inline-block;
            padding: 11px 18px;
            border: none;
            border-radius: 9px;
            cursor: pointer;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
        }

        .boton-guardar {
            background: #5145e5;
            color: white;
            margin-top: 20px;
        }

        .boton-guardar:hover {
            background: #30258f;
        }

        .boton-cancelar {
            background: #eee;
            color: #444;
            margin-top: 20px;
        }

        .tabla-contenedor {
            background: white;
            padding: 22px;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(48, 37, 143, .08);
            overflow-x: auto;
        }

        .tabla-contenedor h3 {
            color: #30258f;
            margin-bottom: 20px;
        }

        .tabla-productos {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        .tabla-productos th,
        .tabla-productos td {
            padding: 13px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .tabla-productos th {
            background: #f5f3ff;
            color: #30258f;
        }

        .tabla-productos img {
            width: 65px;
            height: 60px;
            object-fit: contain;
            border-radius: 8px;
            background: #f7f7fa;
        }

        .boton-editar {
            background: #e9e7ff;
            color: #5145e5;
        }

        .boton-eliminar {
            background: #ffe9e9;
            color: #c62828;
        }

        .mensaje {
            padding: 13px;
            border-radius: 9px;
            margin-bottom: 20px;
        }

        .mensaje.exito {
            background: #e8f7ed;
            color: #24733e;
        }

        .mensaje.error {
            background: #fff0f0;
            color: #c62828;
        }

        .acciones {
            display: flex;
            gap: 7px;
            align-items: center;
        }

        @media (max-width: 650px) {
            .campos {
                grid-template-columns: 1fr;
            }

            .campo.completo {
                grid-column: auto;
            }
        }
    </style>
</head>

<body>

<header class="encabezado">
    <h1>NOVA MARKET</h1>

    <nav>
        <a href="index.php">Inicio</a>
        <a href="productos.php">Tienda</a>
        <a href="pedidos.php">Pedidos</a>
        <a href="cerrar_sesion.php">Cerrar sesión</a>
    </nav>
</header>

<main class="admin-contenedor">

    <div class="admin-titulo">
        <h2>Administración de productos</h2>
        <p>Agrega, modifica y administra los productos de tu tienda.</p>
    </div>

    <?php if ($mensaje !== "") { ?>
        <div class="mensaje <?php echo $tipo_mensaje; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php } ?>

    <section class="producto-formulario">

        <h3>
            <?php echo $producto_editar ? "Editar producto" : "Agregar nuevo producto"; ?>
        </h3>

        <form method="POST">

            <input type="hidden" name="token"
                   value="<?php echo htmlspecialchars($token); ?>">

            <input type="hidden" name="accion"
                   value="<?php echo $producto_editar ? "editar" : "agregar"; ?>">

            <?php if ($producto_editar) { ?>
                <input type="hidden" name="id"
                       value="<?php echo (int) $producto_editar["id"]; ?>">
            <?php } ?>

            <div class="campos">

                <div class="campo">
                    <label for="nombre">Nombre del producto</label>
                    <input type="text" id="nombre" name="nombre"
                           value="<?php echo htmlspecialchars($producto_editar["nombre"] ?? ""); ?>"
                           required>
                </div>

                <div class="campo">
                    <label for="precio">Precio ($)</label>
                    <input type="number" id="precio" name="precio"
                           min="0.01" step="0.01"
                           value="<?php echo htmlspecialchars($producto_editar["precio"] ?? ""); ?>"
                           required>
                </div>

                <div class="campo completo">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion"><?php
                        echo htmlspecialchars($producto_editar["descripcion"] ?? "");
                    ?></textarea>
                </div>

                <div class="campo">
                    <label for="imagen">Nombre del archivo de imagen</label>
                    <input type="text" id="imagen" name="imagen"
                           placeholder="Ejemplo: laptop.png"
                           value="<?php echo htmlspecialchars($producto_editar["imagen"] ?? ""); ?>"
                           required>
                </div>

                <div class="campo">
                    <label for="id_vendedor">Vendedor</label>
                    <select id="id_vendedor" name="id_vendedor" required>
                        <option value="">Selecciona un vendedor</option>

                        <?php while ($vendedor = $vendedores->fetch_assoc()) { ?>
                            <option value="<?php echo (int) $vendedor["id"]; ?>"
                                <?php
                                echo (
                                    isset($producto_editar["id_vendedor"]) &&
                                    $producto_editar["id_vendedor"] == $vendedor["id"]
                                ) ? "selected" : "";
                                ?>>
                                <?php echo htmlspecialchars($vendedor["nombre"]); ?>
                            </option>
                        <?php } ?>

                    </select>
                </div>

            </div>

            <button type="submit" class="boton boton-guardar">
                <?php echo $producto_editar ? "Guardar cambios" : "Agregar producto"; ?>
            </button>

            <?php if ($producto_editar) { ?>
                <a href="admin_productos.php" class="boton boton-cancelar">
                    Cancelar edición
                </a>
            <?php } ?>

        </form>

    </section>

    <section class="tabla-contenedor">

        <h3>Productos registrados</h3>

        <table class="tabla-productos">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Imagen</th>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Vendedor</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>

                <?php if ($productos && $productos->num_rows > 0) { ?>

                    <?php while ($producto = $productos->fetch_assoc()) { ?>

                        <tr>
                            <td><?php echo (int) $producto["id"]; ?></td>

                            <td>
                                <img
                                    src="imagenes/<?php echo rawurlencode(basename($producto["imagen"])); ?>"
                                    alt="<?php echo htmlspecialchars($producto["nombre"]); ?>"
                                >
                            </td>

                            <td><?php echo htmlspecialchars($producto["nombre"]); ?></td>

                            <td>
                                $<?php echo number_format((float) $producto["precio"], 2); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($producto["vendedor"] ?? "Sin vendedor"); ?>
                            </td>

                            <td>
                                <div class="acciones">

                                    <a class="boton boton-editar"
                                       href="admin_productos.php?editar=<?php echo (int) $producto["id"]; ?>">
                                        Editar
                                    </a>

                                    <form method="POST"
                                          onsubmit="return confirm('¿Seguro que deseas eliminar este producto?');">

                                        <input type="hidden" name="token"
                                               value="<?php echo htmlspecialchars($token); ?>">

                                        <input type="hidden" name="accion" value="eliminar">

                                        <input type="hidden" name="id"
                                               value="<?php echo (int) $producto["id"]; ?>">

                                        <button type="submit" class="boton boton-eliminar">
                                            Eliminar
                                        </button>

                                    </form>

                                </div>
                            </td>
                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="6">Todavía no hay productos registrados.</td>
                    </tr>

                <?php } ?>

            </tbody>
        </table>

    </section>

</main>

<footer class="pie">
    <p>© 2026 NOVA MARKET. Todos los derechos reservados.</p>
</footer>

</body>
</html>
