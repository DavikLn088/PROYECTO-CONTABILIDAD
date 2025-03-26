<?php
// config.php

define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
define('DB_NAME', 'sistema_empresas');

// Función para conexión a la base de datos
function conectarDB() {
    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }
    
    $conexion->set_charset("utf8mb4");
    return $conexion;
}

// Función para subir archivos
function subirArchivo($file, $directorio = 'uploads/') {
    if (!file_exists($directorio)) {
        mkdir($directorio, 0755, true);
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($extension, $extensionesPermitidas)) {
        $nombreArchivo = uniqid() . '.' . $extension;
        $rutaArchivo = $directorio . $nombreArchivo;
        
        if (move_uploaded_file($file['tmp_name'], $rutaArchivo)) {
            return $rutaArchivo;
        }
    }
    
    return null;
}
?>