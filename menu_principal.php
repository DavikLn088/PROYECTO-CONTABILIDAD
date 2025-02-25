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
    <title>Menú Principal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #f4f4f4;
            padding: 20px;
        }

        .menu-container {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .menu-item {
            padding: 15px 30px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            font-size: 20px;
            border-radius: 5px;
        }

        .menu-item:hover {
            background-color: #2980b9;
        }

        .btn-index {

            margin-top: 5px;
        }
    </style>
</head>
<body>

    <h2>Bienvenido, <?php echo $_SESSION['usuario']; ?></h2>
    <h3>Seleccione una opción</h3>

    <div class="menu-container">
        <a href="principal.php" class="menu-item">Rol de Pagos</a>
        <button id="btn_cerrar_sesion"><a href="./php/cerrar_sesion.php">Volver a Inicio de Sesion</a></button>
        <a href="facturacion.php" class="menu-item">Facturación</a> 
    </div>
</body>
</html>

