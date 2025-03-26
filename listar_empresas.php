<?php
session_start();
require_once './php/config.php';

// Verificar sesión y redireccionar si no está logueado
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['error_message'] = 'Debe iniciar sesión primero';
    header('Location: ../index.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Procesar eliminación de empresa si se envió el parámetro
if (isset($_GET['eliminar'])) {
    $id = filter_input(INPUT_GET, 'eliminar', FILTER_VALIDATE_INT);
    
    if ($id) {
        try {
            $pdo = conectarDB();
            // Verificar que la empresa pertenece al usuario antes de eliminar
            $stmt = $pdo->prepare("DELETE FROM empresas WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$id, $usuario_id]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['success_message'] = 'Empresa eliminada correctamente';
            } else {
                $_SESSION['error_message'] = 'No se pudo eliminar la empresa o no tienes permisos';
            }
            header('Location: menu_principal.php');
            exit;
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error al eliminar empresa: " . $e->getMessage();
        }
    }
}

// Obtener solo las empresas del usuario logueado
try {
    $pdo = conectarDB();
    $sql = "SELECT id, ruc, razon_social, nombre_comercial, tipo_ambiente 
            FROM empresas 
            WHERE usuario_id = ?
            ORDER BY razon_social";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id]);
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error al obtener empresas: " . $e->getMessage();
    $empresas = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empresas</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: all 0.3s;
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
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #3498db;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .ambiente-produccion {
            background-color: rgba(231, 76, 60, 0.1);
        }
        .ambiente-pruebas {
            background-color: rgba(46, 204, 113, 0.1);
        }
        .actions a {
            margin-right: 8px;
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
        .alert-success {
            background-color: #ddffdd;
            color: #007700;
        }
        .back-btn {
            margin-bottom: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="menu_principal.php" class="btn btn-primary back-btn">← Volver al Menú Principal</a>
        
        <h1>Mis Empresas</h1>
        
        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($_SESSION['error_message']); ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success_message']); ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <a href="nueva_empresa.php" class="btn btn-success">+ Agregar Nueva Empresa</a>
        
        <div class="table-container">
            <?php if(empty($empresas)): ?>
                <p>No tienes empresas registradas.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>RUC</th>
                            <th>Razón Social</th>
                            <th>Nombre Comercial</th>
                            <th>Ambiente</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empresas as $empresa): ?>
                            <tr class="<?= $empresa['tipo_ambiente'] === 'PRODUCCION' ? 'ambiente-produccion' : 'ambiente-pruebas' ?>">
                                <td><?= htmlspecialchars($empresa['ruc']) ?></td>
                                <td><?= htmlspecialchars($empresa['razon_social']) ?></td>
                                <td><?= htmlspecialchars($empresa['nombre_comercial']) ?></td>
                                <td><?= htmlspecialchars($empresa['tipo_ambiente']) ?></td>
                                <td class="actions">
                                    <a href="ver_empresa.php?id=<?= $empresa['id'] ?>" class="btn btn-primary">Ver</a>
                                    <a href="editar_empresa.php?id=<?= $empresa['id'] ?>" class="btn btn-success">Editar</a>
                                    <a href="listar_empresas.php?eliminar=<?= $empresa['id'] ?>" class="btn btn-danger" 
                                       onclick="return confirm('¿Está seguro de eliminar esta empresa?')">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>