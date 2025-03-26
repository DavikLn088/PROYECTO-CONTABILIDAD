<?php
session_start();
require_once './php/config.php';

// Validar token
if (!isset($_GET['token'])) {
    header("Location: index.php");
    exit();
}

$token = $_GET['token'];

try {
    $pdo = conectarDB();
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND token_expira > NOW()");
    $stmt->execute([$token]);
    
    if ($stmt->rowCount() !== 1) {
        $_SESSION['error_message'] = "Enlace inválido o expirado";
        header("Location: index.php");
        exit();
    }
    
    $user = $stmt->fetch();
    $user_id = $user['id'];
    
    // Procesar cambio de contraseña
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error_message'] = 'Token de seguridad inválido';
            header("Location: reset_password.php?token=$token");
            exit();
        }
        
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($password !== $confirm_password) {
            $_SESSION['error_message'] = "Las contraseñas no coinciden";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE usuarios SET password_hash = ?, reset_token = NULL, token_expira = NULL WHERE id = ?");
            $update->execute([$hash, $user_id]);
            
            $_SESSION['success_message'] = "Contraseña actualizada correctamente";
            header("Location: index.php");
            exit();
        }
    }
} catch (PDOException $e) {
    error_log("Error en reset: " . $e->getMessage());
    $_SESSION['error_message'] = "Error al procesar la solicitud";
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña</title>
    <link rel="stylesheet" href="./css/style_password.css">
</head>
<body>
    <main class="auth-container">
        <div class="auth-box">
            <h2>Crear Nueva Contraseña</h2>
            
            <?php if(isset($_SESSION['error_message'])): ?>
                <div class="alert error"><?= $_SESSION['error_message']; ?></div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label for="password">Nueva Contraseña</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn-primary">Actualizar Contraseña</button>
            </form>
        </div>
    </main>
</body>
</html>