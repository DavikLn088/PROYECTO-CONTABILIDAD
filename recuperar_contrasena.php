<?php
session_start();
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require './php/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// 1. Generar token CSRF (si no existe)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 2. Procesar formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF primero
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = 'Token de seguridad inválido';
        header("Location: recuperar_contrasena.php");
        exit();
    }

    // 3. Validar campos del formulario
    if (empty($_POST['email'])) {
        $_SESSION['error_message'] = 'El email es requerido';
        header("Location: recuperar_contrasena.php");
        exit();
    }

    // 4. Conexión a DB y procesamiento (esto SÍ va después de las validaciones)
    try {
        $pdo = conectarDB();
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        
        // Verificar si el email existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() === 1) {
            $token = bin2hex(random_bytes(50));
            $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));
            
            $update = $pdo->prepare("UPDATE usuarios SET reset_token = ?, token_expira = ? WHERE correo = ?");
            $update->execute([$token, $expira, $email]);
            
            $mail = new PHPMailer(true);
            
            try {
                // Configuración SMTP para Gmail
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'jeremyburgos826@gmail.com'; // Tu email
                $mail->Password = 'zieb ticv zehw caqp'; // Contraseña de aplicación
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Cambiado a STARTTLS
                $mail->Port = 587; // Puerto alternativo
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
                
                // Configuración del email
                $mail->setFrom('jeremyburgos826@gmail.com', 'Sistema de Recuperacion');
                $mail->addAddress($email);
                
                $reset_link = "http://".$_SERVER['HTTP_HOST']."/PROYECTO-CONTABILIDAD/reset_password.php?token=$token";
                
                $mail->isHTML(true);
                $mail->Subject = 'Recuperacion de contrasena';
                $mail->Body = "
                    <h2>Restablece tu contraseña</h2>
                    <p>Haz clic en el enlace para continuar:</p>
                    <a href='$reset_link'>Restablecer contraseña</a>
                    <p>Si no solicitaste esto, ignora este correo.</p>
                "; 
                $mail->send();
                $_SESSION['success_message'] = "Correo enviado a $email";
            } catch (Exception $e) {
                error_log("Error al enviar correo: ".$mail->ErrorInfo);
                $_SESSION['error_message'] = "Error al enviar el correo. Intenta más tarde.";
            }
        }
        
    } catch (PDOException $e) {
        error_log("Error en recuperación: " . $e->getMessage());
        $_SESSION['error_message'] = "Error en el sistema. Contacte al administrador.";
        header("Location: recuperar_contrasena.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="./css/style_password.css">
</head>
<body>
    <main class="auth-container">
        <div class="auth-box">
            <h2>Recuperar Contraseña</h2>
            
            <?php if(isset($_SESSION['error_message'])): ?>
                <div class="alert error"><?= $_SESSION['error_message']; ?></div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['success_message'])): ?>
                <div class="alert success"><?= $_SESSION['success_message']; ?></div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <button type="submit" class="btn-primary">Enviar Enlace</button>
            </form>
            
            <div class="auth-links">
                <a href="index.php">Volver al Login</a>
            </div>
        </div>
    </main>
</body>
</html>