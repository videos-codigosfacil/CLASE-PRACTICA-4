
<?php
require_once "config/conexion.php";

$sql = "SELECT productos.*, vendedores.nombre AS vendedor
        FROM productos
        INNER JOIN vendedores
        ON productos.id_vendedor = vendedores.id";

$resultado = $conexion->query($sql);

if (!$resultado) {
    die("Error al cargar los productos: " . $conexion->error);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Productos | NOVA MARKET</title>

    <link rel="stylesheet" href="css/estilo.css">
</head>

<body>

<header class="encabezado">
    <h1>NOVA MARKET</h1>

    <nav>
        <a href="index.php">Inicio</a>
        <a href="productos.php">Productos</a>
        <a href="mis_pedidos.php">Mis pedidos</a>
    </nav>
</header>

<main class="contenedor">

    <h2>Nuestros productos</h2>

    <p>Descubre nuestra variedad de productos disponibles.</p>

    <section class="productos">

        <?php if ($resultado->num_rows > 0) { ?>

            <?php while ($producto = $resultado->fetch_assoc()) { ?>

                <article class="tarjeta">

                    <img
                        src="imagenes/<?php echo htmlspecialchars($producto['imagen']); ?>"
                        alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                    >

                    <div class="contenido-tarjeta">

                        <h3>
                            <?php echo htmlspecialchars($producto['nombre']); ?>
                        </h3>

                        <p>
                            <?php echo htmlspecialchars($producto['descripcion']); ?>
                        </p>

                        <p class="vendedor">
                            Vendedor:
                            <?php echo htmlspecialchars($producto['vendedor']); ?>
                        </p>

                        <strong>
                            $<?php echo number_format($producto['precio'], 2); ?>
                        </strong>

                        <a
                            class="boton"
                            href="comprar.php?id=<?php echo $producto['id']; ?>"
                        >
                            Comprar
                        </a>

                    </div>

                </article>

            <?php } ?>

        <?php } else { ?>

            <p>No hay productos disponibles por el momento.</p>

        <?php } ?>

    </section>

</main>

<footer class="pie">
    <p>© 2026 NOVA MARKET. Todos los derechos reservados.</p>
</footer>

</body>
</html>
