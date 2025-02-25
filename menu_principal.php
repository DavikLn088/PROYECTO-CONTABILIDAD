<?php
session_start();
if (!isset($_SESSION['correo'])) {
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
    <title>Menú Principal</title>
    <style>
body {
    font-family: Arial, sans-serif;
    text-align: center;
    background-color: #f4f4f4;
    background-image: url(./images/fondo_de_pantalla.jpeg);
    padding: 20px;
}

.contenedor-texto {
    background: rgba(0, 0, 0, 0.6); /* Fondo oscuro semi-transparente */
    padding: 20px;
    border-radius: 10px; /* Bordes redondeados */
    display: inline-block;
    color: white; /* Letras blancas */
    box-shadow: 5px 5px 15px rgba(0, 0, 0, 0.3); /* Efecto difuminado */
}

h2 {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 10px;
}

h3 {
    font-size: 20px;
    margin-bottom: 20px;
}

.menu-container {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 20px;
}

.menu-item, .btn-index {
    display: inline-block; 
    padding: 15px 30px;
    background-color: #3498db;
    color: white !important;
    text-decoration: none;
    font-size: 20px;
    font-weight: bold;
    border-radius: 10px;
    text-align: center;
    box-shadow: 3px 3px 10px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease-in-out;
}

.menu-item:hover, .btn-index:hover {
    background-color: #217dbb;
    transform: scale(1.05);
}
</style>
</head>
<body>

<div class="contenedor-texto">
    <h2>Bienvenido, <?php echo $_SESSION['correo']; ?></h2>
    <h3>Seleccione una opción.</h3>
</div>

<div class="menu-container">
    <a href="principal.php" class="menu-item">Rol de Pagos</a>
    <a href="./php/cerrar_sesion.php" class="btn-index">Volver a Inicio de Sesión</a>
    <a href="facturacion.php" class="menu-item">Facturación</a>
</div>

</body>
</html>

