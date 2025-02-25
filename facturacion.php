<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    echo "<script>
            alert('Por favor debes iniciar sesión');
            window.location.href = '../index.php';
          </script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación</title>
</head>
<body>

    <h2>Página de Facturación</h2>
    <p>Aquí irá el sistema de facturación.</p>
    <a href="menu_principal.php">Volver al Menú Principal</a>

</body>
</html>

