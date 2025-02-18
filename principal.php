<?php
    session_start();

    if(!isset($_SESSION['usuario'])){
        echo '
            <script>
                alert("Por favor debes iniciar sesion");
                window.location = "index.php";
            </script>
        ';
        session_destroy();
        die();
    }

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Principal</title>
    <link rel="stylesheet" href="./css/style_principal.css">
</head>
<body>
    
    <main>
        <div class="contenedor__todo">
            <div class="caja_rol_de_pagos">
                <div class="caja__trasera_rol">
                    <h3>Rol de pagos</h3>
                            <button id="btn__ingresar">Ingresar valores</button>
                </div>
        <div class="cerrar_sesion">
        <button id="btn_cerrar_sesion"><a href="./php/cerrar_sesion.php">Cerrar sesion</a></button>
</body>
</html>