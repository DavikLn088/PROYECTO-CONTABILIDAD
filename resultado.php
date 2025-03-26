<?php
// resultado.php

require_once './php/config.php';
session_start();

if (!isset($_SESSION['empresa_id'])) {
    header('Location: index.php');
    exit;
}

$idEmpresa = $_SESSION['empresa_id'];
unset($_SESSION['empresa_id']);

// Obtener los datos de la empresa desde la base de datos
$conexion = conectarDB();
$sql = "SELECT * FROM empresas WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $idEmpresa);
$stmt->execute();
$resultado = $stmt->get_result();
$empresa = $resultado->fetch_assoc();
$stmt->close();
$conexion->close();

if (!$empresa) {
    die("Empresa no encontrada");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datos Procesados</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        h2 { color: #4CAF50; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 8px; }
        .data-table th { background-color: #f2f2f2; text-align: left; }
        .data-table tr:nth-child(even) { background-color: #f9f9f9; }
        .logo-preview { max-width: 200px; max-height: 200px; }
        .success-message { 
            background-color: #dff0d8; 
            color: #3c763d; 
            padding: 15px; 
            border-radius: 4px; 
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-message">
            <h2>¡Datos guardados correctamente!</h2>
            <p>La información de la empresa ha sido registrada en el sistema.</p>
        </div>
        
        <h3>Resumen de los datos:</h3>
        
        <table class="data-table">
            <tr>
                <th>Campo</th>
                <th>Valor</th>
            </tr>
            <?php foreach ($empresa as $campo => $valor): ?>
                <?php if ($campo === 'id' || $campo === 'fecha_creacion' || $campo === 'fecha_actualizacion') continue; ?>
                <tr>
                    <td><?= ucfirst(str_replace('_', ' ', $campo)) ?></td>
                    <td>
                        <?php if ($campo === 'logo' && !empty($valor)): ?>
                            <img src="<?= $valor ?>" alt="Logo" class="logo-preview">
                        <?php elseif (in_array($campo, ['obligado_contabilidad', 'exportador_bienes', 'contribuyente_rimpe'])): ?>
                            <span style="color: <?= $valor === 'SI' ? 'green' : '#666' ?>; font-weight: <?= $valor === 'SI' ? 'bold' : 'normal' ?>">
                                <?= $valor ?>
                            </span>
                        <?php else: ?>
                            <?= htmlspecialchars($valor) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        
        <p style="margin-top: 20px;">
            <a href="index.php">Volver al formulario</a> | 
            <a href="listar_empresas.php">Ver todas las empresas</a>
        </p>
    </div>
</body>
</html>