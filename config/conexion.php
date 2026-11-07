<?php

$host = "sql103.infinityfree.com";
$usuario = "if0_43316380";
$contrasena = "gO42jHyswGV";
$base_datos = "if0_43316380_tienda_productos";

$conexion = new mysqli(
    $host,
    $usuario,
    $contrasena,
    $base_datos
);

if ($conexion->connect_error) {
    die("Error de conexión a la base de datos.");
}

$conexion->set_charset("utf8mb4");
?>
