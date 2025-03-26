<?php
session_start();

// Habilitar reporte de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Validar token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = 'Token de seguridad inválido';
    header("Location: ../index.php");
    exit();
}

// Validar campos requeridos
$required = ['nombre_completo', 'correo', 'contrasena', 'confirmar_contrasena'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['error_message'] = "El campo " . str_replace('_', ' ', $field) . " es requerido";
        header("Location: ../index.php");
        exit();
    }
}

// Validar que las contraseñas coincidan
if ($_POST['contrasena'] !== $_POST['confirmar_contrasena']) {
    $_SESSION['error_message'] = 'Las contraseñas no coinciden';
    header("Location: ../index.php");
    exit();
}

// Validar fortaleza de contraseña
if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $_POST['contrasena'])) {
    $_SESSION['error_message'] = 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número';
    header("Location: ../index.php");
    exit();
}

// Validar formato de nombre
if (!preg_match('/^[A-Za-z áéíóúÁÉÍÓÚñÑ]+$/', $_POST['nombre_completo'])) {
    $_SESSION['error_message'] = 'El nombre solo puede contener letras y espacios';
    header("Location: ../index.php");
    exit();
}
// Validar reCAPTCHA
if (isset($_POST['g-recaptcha-response'])) {
    $recaptcha_secret = "6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe";
    $recaptcha_response = $_POST['g-recaptcha-response'];
    
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_data = [
        'secret' => $recaptcha_secret,
        'response' => $recaptcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($recaptcha_data)
        ]
    ];
    
    $context = stream_context_create($options);
    $recaptcha_result = file_get_contents($recaptcha_url, false, $context);
    $recaptcha_json = json_decode($recaptcha_result);
    
    if (!$recaptcha_json->success) {
        $_SESSION['error_message'] = 'Por favor completa el CAPTCHA correctamente';
        header("Location: ../index.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = 'Error de verificación CAPTCHA';
    header("Location: ../index.php");
    exit();
}

// Conectar a la base de datos
require_once 'config.php';

try {
    $pdo = conectarDB();

    // Sanitizar inputs
    $nombre = htmlspecialchars(trim($_POST['nombre_completo']));
    $correo = filter_var(trim($_POST['correo']), FILTER_SANITIZE_EMAIL);
    $contrasena = $_POST['contrasena'];

    // Validar formato de correo
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = 'El correo electrónico no es válido';
        header("Location: ../index.php");
        exit();
    }

    // Verificar si el correo ya existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['error_message'] = 'El correo electrónico ya está registrado';
        header("Location: ../index.php");
        exit();
    }

    // Hash de la contraseña
    $password_hash = password_hash($contrasena, PASSWORD_BCRYPT, ['cost' => 12]);

    // Insertar nuevo usuario
    $sql = "INSERT INTO usuarios (correo, password_hash, nombre_completo) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$correo, $password_hash, $nombre])) {
        $_SESSION['success_message'] = 'Registro exitoso. Por favor inicia sesión.';
        header("Location: ../index.php");
        exit();
    } else {
        $_SESSION['error_message'] = 'Error al registrar el usuario';
        header("Location: ../index.php");
        exit();
    }

} catch (PDOException $e) {
    error_log('Error en registro: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Error en el sistema: ' . $e->getMessage();
    header("Location: ../index.php");
    exit();
}
?>