<?php
require_once './php/config.php';

session_start();
$empresa_id = $_SESSION['empresa_id'] ?? null;

if (!$empresa_id) {
    header('Location: index.php');
    exit;
}

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_identificacion = limpiarDato($_POST['tipo_identificacion']);
    $identificacion = limpiarDato($_POST['identificacion']);
    $nombre = limpiarDato($_POST['nombre']);
    $direccion = limpiarDato($_POST['direccion']);
    $telefono = limpiarDato($_POST['telefono']);
    $email = limpiarDato($_POST['email']);

    $conexion = conectarDB();
    $sql = "INSERT INTO clientes (empresa_id, tipo_identificacion, identificacion, nombre, direccion, telefono, email) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("issssss", $empresa_id, $tipo_identificacion, $identificacion, $nombre, $direccion, $telefono, $email);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = 'Cliente registrado correctamente';
        header('Location: facturacion.php');
        exit;
    } else {
        $error = "Error al registrar el cliente: " . $stmt->error;
    }
    
    $stmt->close();
    $conexion->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Cliente</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container { 
            max-width: 600px; 
            margin: 0 auto; 
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-top: 0;
        }
        .form-group { 
            margin-bottom: 15px;
        }
        label { 
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        input[type="text"], 
        input[type="email"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn { 
            padding: 10px 15px; 
            background-color: #4CAF50; 
            color: white; 
            border: none; 
            border-radius: 4px;
            cursor: pointer; 
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .error {
            color: #dc3545;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Registrar Nuevo Cliente</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <form action="nuevo_cliente.php" method="POST">
            <div class="form-group">
                <label for="tipo_identificacion">Tipo de Identificación:</label>
                <select id="tipo_identificacion" name="tipo_identificacion" required>
                    <option value="04">RUC</option>
                    <option value="05">Cédula</option>
                    <option value="06">Pasaporte</option>
                    <option value="07">Consumidor Final</option>
                    <option value="08">Identificación del Exterior</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="identificacion">Número de Identificación:</label>
                <input type="text" id="identificacion" name="identificacion" required>
            </div>
            
            <div class="form-group">
                <label for="nombre">Nombre/Razón Social:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            
            <div class="form-group">
                <label for="direccion">Dirección:</label>
                <input type="text" id="direccion" name="direccion">
            </div>
            
            <div class="form-group">
                <label for="telefono">Teléfono:</label>
                <input type="tel" id="telefono" name="telefono">
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email">
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" class="btn">Guardar Cliente</button>
                <a href="facturacion.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>