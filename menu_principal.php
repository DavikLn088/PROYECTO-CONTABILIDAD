<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Principal</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            text-align: center;
            margin: 0;
            padding: 0;
            background-image: url('./images/fondo_de_pantalla.jpeg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .contenedor-texto {
            background: rgba(0, 0, 0, 0.75);
            padding: 30px 50px;
            border-radius: 15px;
            color: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            margin-bottom: 40px;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            width: 50%;
            max-width: 600px;
        }
        .user-role {
            font-size: 16px;
            color: #4ecdc4;
            margin: 8px 0 0;
            font-weight: 600;
        }

        .last-login {
            font-size: 14px;
            color: #aaa;
            margin: 10px 0 0;
            font-style: italic;
        }
        
        h2 {
            margin: 5px;
            font-size: 2rem;
            margin-bottom: 8px;
            color: #fff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            font-weight: 600;
        }

        h3 {
            margin: 5px;
            font-size: 1.4rem;
            margin-bottom: 5px;
            color: #eee;
            font-weight: 400;
        }

        .menu-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 25px;
            max-width: 900px;
        }

        .menu-item {
            padding: 18px 35px;
            background: linear-gradient(135deg, #3498db, #2c3e50);
            color: white !important;
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            min-width: 200px;
            border: none;
            cursor: pointer;
        }

        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 7px 20px rgba(0, 0, 0, 0.3);
            background: linear-gradient(135deg, #2980b9, #1a252f);
        }

        .btn-logout {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white !important;
        }

        .btn-logout:hover {
            background: linear-gradient(135deg, #c0392b, #992d22);
            transform: translateY(-5px);
        }

        @media (max-width: 768px) {
            .menu-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .contenedor-texto {
                padding: 20px;
                width: 90%;
            }
        }
    </style>
</head>
<body>

<div class="contenedor-texto">
    <h2>Bienvenido, <?php 
        // Opción 1: Accediendo al array usuario
        if(isset($_SESSION['usuario']['nombre_completo'])) {
            echo htmlspecialchars($_SESSION['usuario']['nombre_completo']);
        } 
        // Opción 2: Mostrar correo si no hay nombre
        elseif(isset($_SESSION['usuario']['correo'])) {
            echo htmlspecialchars($_SESSION['usuario']['correo']);
        }
        // Opción 3: Mensaje por defecto
        else {
            echo 'Invitado';
        }
    ?></h2>
    <h3>Seleccione una opción</h3>
    <?php if(isset($_SESSION['usuario']['rol'])): ?>
        <p class="user-role">Rol: <?php echo htmlspecialchars(ucfirst($_SESSION['usuario']['rol'])); ?></p>
    <?php endif; ?>
</div>

<div class="menu-container">
    <a href="rol_de_pagos.php" class="menu-item">Rol de Pagos</a>
    <a href="facturacion.php" class="menu-item">Facturación</a>
    <a href="./php/cerrar_sesion.php" class="menu-item btn-logout">Cerrar Sesión</a>
</div>
</body>
</html>

