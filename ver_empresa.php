<?php
session_start();
require_once './php/config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

$empresa = null;

if (isset($_GET['id'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if ($id) {
        try {
            $pdo = conectarDB();
            $stmt = $pdo->prepare("SELECT * FROM empresas WHERE id = ?");
            $stmt->execute([$id]);
            $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$empresa) {
                $_SESSION['error_message'] = 'Empresa no encontrada';
                header('Location: listar_empresas.php');
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error al obtener empresa: " . $e->getMessage();
            header('Location: listar_empresas.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Empresa</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .empresa-details {
            margin-bottom: 30px;
        }
        .detail-row {
            display: flex;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .detail-label {
            font-weight: bold;
            width: 200px;
            color: #555;
        }
        .detail-value {
            flex: 1;
        }
        .logo-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: all 0.3s;
            margin-right: 10px;
        }
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background-color: #c0392b;
        }
        .btn-success {
            background-color: #2ecc71;
            color: white;
        }
        .btn-success:hover {
            background-color: #27ae60;
        }
        .back-btn {
            margin-bottom: 20px;
            display: inline-block;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-error {
            background-color: #ffdddd;
            color: #cc0000;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="listar_empresas.php" class="btn btn-primary back-btn">← Volver al Listado</a>
        
        <h1>Detalles de Empresa</h1>
        
        <?php if ($empresa): ?>
        <div class="empresa-details">
            <div class="detail-row">
                <div class="detail-label">RUC:</div>
                <div class="detail-value"><?= htmlspecialchars($empresa['ruc']) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Razón Social:</div>
                <div class="detail-value"><?= htmlspecialchars($empresa['razon_social']) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Nombre Comercial:</div>
                <div class="detail-value"><?= htmlspecialchars($empresa['nombre_comercial']) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Dirección Matriz:</div>
                <div class="detail-value"><?= htmlspecialchars($empresa['direccion_matriz']) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Dirección Establecimiento:</div>
                <div class="detail-value"><?= htmlspecialchars($empresa['direccion_establecimiento']) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Código Establecimiento:</div>
                <div class="detail-value"><?= htmlspecialchars($empresa['codigo_establecimiento']) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Código Punto de Emisión:</div>
                <div class="detail-value"><?= htmlspecialchars($empresa['codigo_punto_emision']) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Contribuyente Especial:</div>
                <div class="detail-value"><?= htmlspecialchars($empresa['contribuyente_especial'] ?? 'No') ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Obligado a llevar contabilidad:</div>
                <div class="detail-value"><?= $empresa['obligado_contabilidad'] === 'SI' ? 'Sí' : 'No' ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Exportador de Bienes:</div>
                <div class="detail-value"><?= $empresa['exportador_bienes'] === 'SI' ? 'Sí' : 'No' ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Contribuyente RIMPE:</div>
                <div class="detail-value"><?= $empresa['contribuyente_rimpe'] === 'SI' ? 'Sí' : 'No' ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Agente de Retención:</div>
                <div class="detail-value"><?= htmlspecialchars($empresa['agente_de_retencion'] ?? 'No') ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Tipo de Ambiente:</div>
                <div class="detail-value"><?= htmlspecialchars($empresa['tipo_ambiente']) ?></div>
            </div>
            
            <?php if (!empty($empresa['logo'])): ?>
            <div class="detail-row">
                <div class="detail-label">Logo:</div>
                <div class="detail-value">
                    <img src="<?= htmlspecialchars($empresa['logo']) ?>" alt="Logo de la empresa" class="logo-preview">
                </div>
            </div>
            <?php endif; ?>
            
            <div style="margin-top: 30px;">
                <a href="editar_empresa.php?id=<?= $empresa['id'] ?>" class="btn btn-success">Editar</a>
                <a href="listar_empresas.php?eliminar=<?= $empresa['id'] ?>" class="btn btn-danger" 
                   onclick="return confirm('¿Está seguro de eliminar esta empresa?')">Eliminar</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>