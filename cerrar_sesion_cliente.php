    
<?php
session_start();

unset(
    $_SESSION["cliente_id"],
    $_SESSION["cliente_nombre"],
    $_SESSION["cliente_correo"],
    $_SESSION["cliente_direccion"]
);

session_regenerate_id(true);

header("Location: index.php");
exit;
?>
