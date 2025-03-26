<?php
session_start();
if (($_SESSION['intentos_login'] ?? 0) >= 3) {
    $_SESSION['error_message'] = 'Demasiados intentos fallidos. Espere 15 minutos.';
    header("Location: ../index.php");
    exit();
}
$_SESSION['intentos_login'] = ($_SESSION['intentos_login'] ?? 0) + 1;
// Validar token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = 'Token de seguridad inválido';
    header("Location: ../index.php");
    exit();
}

// Validar campos
if (empty($_POST['correo']) || empty($_POST['contrasena'])) {
    $_SESSION['error_message'] = 'Todos los campos son requeridos';
    header("Location: ../index.php");
    exit();
}

// Conectar a la base de datos
require_once 'config.php';

try {
    $pdo = conectarDB();

    // Sanitizar inputs
    $correo = filter_var($_POST['correo'], FILTER_SANITIZE_EMAIL);
    $contrasena = $_POST['contrasena'];

    // Buscar usuario
    $sql = "SELECT id, correo, password_hash, nombre_completo, rol FROM usuarios WHERE correo = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$correo]);
    
    if ($stmt->rowCount() === 1) {
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar contraseña
        if (password_verify($contrasena, $usuario['password_hash'])) {
            // Iniciar sesión
            $_SESSION['usuario'] = [
                'id' => $usuario['id'],
                'correo' => $usuario['correo'],
                'nombre_completo' => $usuario['nombre_completo'], 
                'rol' => $usuario['rol'],
                'ultimo_login' => $usuario['ultimo_login'] // Asegúrate de actualizarlo al hacer login
            ];
            $_SESSION['nombre'] = $usuario['nombre_completo'];
            $_SESSION['correo'] = $usuario['correo'];
            $_SESSION['rol'] = $usuario['rol'];
            
            // Regenerar ID de sesión por seguridad
            session_regenerate_id(true);
            
            // Actualizar último login
            $update_sql = "UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$usuario['id']]);
            
            header("Location: ../menu_principal.php");
            exit();
        }
    }
    
    // Si llega aquí, las credenciales son incorrectas
    $_SESSION['error_message'] = 'Correo o contraseña incorrectos';
    header("Location: ../index.php");
    exit();

} catch (PDOException $e) {
    error_log('Error en login: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Error en el sistema. Contacte al administrador.';
    header("Location: ../index.php");
    exit();
}
?>