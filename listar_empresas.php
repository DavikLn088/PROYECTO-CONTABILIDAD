<?php
// listar_empresas.php

require_once './php/config.php';

// Obtener todas las empresas de la base de datos
$conexion = conectarDB();
$sql = "SELECT id, ruc, razon_social, nombre_comercial, tipo_ambiente FROM empresas ORDER BY fecha_creacion DESC";
$resultado = $conexion->query($sql);
$empresas = $resultado->fetch_all(MYSQLI_ASSOC);
$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Empresas</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; }
        .empresas-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .empresas-table th, .empresas-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .empresas-table th { background-color: #4CAF50; color: white; }
        .empresas-table tr:nth-child(even) { background-color: #f2f2f2; }
        .empresas-table tr:hover { background-color: #e9e9e9; }
        .ambiente-produccion { background-color: #ffcccc; }
        .ambiente-pruebas { background-color: #ccffcc; }
        .actions { white-space: nowrap; }
        .actions a { margin-right: 5px; text-decoration: none; color: #0066cc; }
        .actions a:hover { text-decoration: underline; }
        .add-button { 
            display: inline-block; 
            padding: 8px 15px; 
            background-color: #4CAF50; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            margin-bottom: 20px;
        }
        .add-button:hover { background-color: #45a049; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Listado de Empresas Registradas</h1>
        
        <a href="index.php" class="add-button">Agregar Nueva Empresa</a>
        
        <table class="empresas-table">
            <thead>
                <tr>
                    <th>RUC</th>
                    <th>Razón Social</th>
                    <th>Nombre Comercial</th>
                    <th>Tipo de Ambiente</th>
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
                            <a href="ver_empresa.php?id=<?= $empresa['id'] ?>">Ver</a>
                            <a href="editar_empresa.php?id=<?= $empresa['id'] ?>">Editar</a>
                            <a href="eliminar_empresa.php?id=<?= $empresa['id'] ?>" onclick="return confirm('¿Está seguro de eliminar esta empresa?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>