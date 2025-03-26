<?php
require_once './php/config.php';

session_start();

if (!isset($_SESSION['factura_procesada']) || !$_SESSION['factura_procesada']) {
    header('Location: facturacion.php');
    exit;
}

// Obtener la última factura generada (simplificado para el ejemplo)
$conexion = conectarDB();
$sql = "SELECT f.*, c.nombre as cliente_nombre, e.razon_social 
        FROM facturas f
        JOIN clientes c ON f.cliente_id = c.id
        JOIN empresas e ON f.empresa_id = e.id
        ORDER BY f.id DESC LIMIT 1";
$factura = $conexion->query($sql)->fetch_assoc();

// Obtener detalles de la factura
$sql_detalles = "SELECT * FROM factura_detalles WHERE factura_id = ?";
$stmt_detalles = $conexion->prepare($sql_detalles);
$stmt_detalles->bind_param("i", $factura['id']);
$stmt_detalles->execute();
$detalles = $stmt_detalles->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_detalles->close();

$conexion->close();

// Limpiar la sesión
unset($_SESSION['factura_procesada']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Factura</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0;
            padding: 20px;
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            border: 1px solid #ddd;
            padding: 20px;
        }
        .header { 
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .company-logo { 
            max-height: 80px;
        }
        .invoice-info { 
            margin-bottom: 30px;
        }
        .invoice-details { 
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .invoice-details th, .invoice-details td { 
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .invoice-details th { 
            background-color: #f2f2f2;
        }
        .totals { 
            margin-left: auto;
            width: 300px;
        }
        .totals table { 
            width: 100%;
            border-collapse: collapse;
        }
        .totals td { 
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .totals tr:last-child td { 
            font-weight: bold;
            border-top: 2px solid #333;
        }
        .status { 
            padding: 10px;
            margin-top: 20px;
            text-align: center;
            font-weight: bold;
            background-color: #dff0d8;
            color: #3c763d;
            border-radius: 4px;
        }
        .actions { 
            margin-top: 30px;
            text-align: center;
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
            margin: 0 5px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h2><?= htmlspecialchars($factura['razon_social']) ?></h2>
                <p>RUC: <?= htmlspecialchars($factura['ruc']) ?></p>
                <p>Dirección Matriz: <?= htmlspecialchars($factura['direccion_matriz']) ?></p>
            </div>
            <div>
                <h1>FACTURA</h1>
                <p>No. <?= htmlspecialchars($factura['numero_factura']) ?></p>
                <p>Fecha: <?= date('d/m/Y', strtotime($factura['fecha_emision'])) ?></p>
            </div>
        </div>

        <div class="invoice-info">
            <p><strong>Cliente:</strong> <?= htmlspecialchars($factura['cliente_nombre']) ?></p>
            <p><strong>Identificación:</strong> <?= htmlspecialchars($factura['identificacion']) ?></p>
            <p><strong>Dirección:</strong> <?= htmlspecialchars($factura['direccion']) ?></p>
        </div>

        <table class="invoice-details">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>P. Unitario</th>
                    <th>Descuento</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $detalle): ?>
                    <tr>
                        <td><?= htmlspecialchars($detalle['codigo']) ?></td>
                        <td><?= htmlspecialchars($detalle['descripcion']) ?></td>
                        <td><?= number_format($detalle['cantidad'], 2) ?></td>
                        <td>$<?= number_format($detalle['precio_unitario'], 2) ?></td>
                        <td><?= number_format($detalle['descuento'], 2) ?>%</td>
                        <td>$<?= number_format($detalle['subtotal'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal 15%:</td>
                    <td>$<?= number_format($factura['subtotal_15'], 2) ?></td>
                </tr>
                <tr>
                    <td>Subtotal 0%:</td>
                    <td>$<?= number_format($factura['subtotal_0'], 2) ?></td>
                </tr>
                <tr>
                    <td>IVA 15%:</td>
                    <td>$<?= number_format($factura['iva'], 2) ?></td>
                </tr>
                <tr>
                    <td>Total:</td>
                    <td>$<?= number_format($factura['total'], 2) ?></td>
                </tr>
            </table>
        </div>

        <?php if ($factura['estado'] === 'AUTORIZADO'): ?>
            <div class="status">
                COMPROBANTE AUTORIZADO<br>
                Número de Autorización: <?= htmlspecialchars($factura['numero_autorizacion']) ?><br>
                Fecha de Autorización: <?= date('d/m/Y H:i:s', strtotime($factura['fecha_autorizacion'])) ?>
            </div>
        <?php endif; ?>

        <div class="actions">
    <a href="facturacion.php" class="btn">Nueva Factura</a>
    <a href="listar_facturas.php" class="btn btn-secondary">Ver Facturas</a>
    <button onclick="window.print()" class="btn">Imprimir</button>
    <a href="descargar_factura.php?id=<?= $factura['id'] ?>" class="btn">Descargar XML</a>
    <a href="generar_pdf.php?id=<?= $factura['id'] ?>" class="btn">Descargar PDF</a>
</div>
    </div>
</body>
</html>