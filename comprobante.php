<?php
session_start();

// Verificar autenticación y factura
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['factura_id'])) {
    $_SESSION['error_message'] = 'Acceso no autorizado';
    header('Location: login.php');
    exit;
}

require_once './php/config.php';

try {
    // Obtener datos completos de la factura
    $sql_factura = "SELECT f.*, 
                   e.ruc as emisor_ruc, e.razon_social as emisor_razon_social, 
                   e.nombre_comercial as emisor_nombre_comercial,
                   e.contribuyente_especial, e.obligado_contabilidad,
                   e.direccion_matriz, e.direccion_establecimiento,
                   c.tipo_identificacion as cliente_tipo_identificacion,
                   c.identificacion as cliente_identificacion,
                   c.nombre as cliente_nombre, c.direccion as cliente_direccion
                   FROM facturas f
                   JOIN empresas e ON f.empresa_id = e.id
                   JOIN clientes c ON f.cliente_id = c.id
                   WHERE f.id = ? AND f.usuario_id = ?";
    
    $stmt_factura = $pdo->prepare($sql_factura);
    $stmt_factura->execute([$_SESSION['factura_id'], $_SESSION['usuario_id']]);
    $factura = $stmt_factura->fetch(PDO::FETCH_ASSOC);
    
    if (!$factura) {
        throw new Exception("Factura no encontrada");
    }
    
    // Obtener detalles de la factura
    $sql_detalles = "SELECT * FROM factura_detalles WHERE factura_id = ?";
    $stmt_detalles = $pdo->prepare($sql_detalles);
    $stmt_detalles->execute([$_SESSION['factura_id']]);
    $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener formas de pago
    $sql_formas_pago = "SELECT * FROM formas_pago_factura WHERE factura_id = ?";
    $stmt_formas_pago = $pdo->prepare($sql_formas_pago);
    $stmt_formas_pago->execute([$_SESSION['factura_id']]);
    $formas_pago = $stmt_formas_pago->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener datos adicionales
    $sql_datos_adicionales = "SELECT * FROM datos_adicionales_factura WHERE factura_id = ?";
    $stmt_datos_adicionales = $pdo->prepare($sql_datos_adicionales);
    $stmt_datos_adicionales->execute([$_SESSION['factura_id']]);
    $datos_adicionales = $stmt_datos_adicionales->fetchAll(PDO::FETCH_ASSOC);
    
    // Limpiar variable de sesión
    unset($_SESSION['factura_id']);
    
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: menu_principal.php');
    exit;
}

// Función para obtener nombre de tipo de identificación
function getTipoIdentificacion($codigo) {
    $tipos = [
        '04' => 'RUC',
        '05' => 'Cédula',
        '06' => 'Pasaporte',
        '07' => 'Consumidor Final',
        '08' => 'Identificación del Exterior'
    ];
    return $tipos[$codigo] ?? $codigo;
}

// Función para formatear moneda
function formatMoney($amount, $moneda = 'USD') {
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'LOCAL' => '$'
    ];
    return ($symbols[$moneda] ?? '$') . number_format($amount, 2);
}
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
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
        }
        h1, h2, h3 {
            color: #333;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .company-info, .invoice-info {
            width: 48%;
        }
        .section {
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .totals {
            margin-top: 20px;
            text-align: right;
            font-size: 1.1em;
        }
        .total-grande {
            font-size: 1.3em;
            font-weight: bold;
            margin-top: 10px;
        }
        .actions {
            margin-top: 30px;
            text-align: center;
        }
        .btn {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 0 10px;
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
        @media print {
            .no-print {
                display: none;
            }
            body {
                background-color: white;
                padding: 0;
            }
            .container {
                box-shadow: none;
                border: none;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="company-info">
                <h1>FACTURA</h1>
                <h2>Datos del Emisor</h2>
                <p><strong>RUC:</strong> <?= htmlspecialchars($factura['emisor_ruc']) ?></p>
                <p><strong>NOMBRE COMERCIAL:</strong> <?= htmlspecialchars($factura['emisor_nombre_comercial']) ?></p>
                <p><strong>RAZÓN SOCIAL:</strong> <?= htmlspecialchars($factura['emisor_razon_social']) ?></p>
                <p><strong>CONTRIBUYENTE ESPECIAL:</strong> <?= $factura['contribuyente_especial'] == 'SI' ? 'SÍ' : 'NO' ?></p>
                <p><strong>OBLIGADO A LLEVAR CONTABILIDAD:</strong> <?= $factura['obligado_contabilidad'] == 'SI' ? 'SÍ' : 'NO' ?></p>
                <p><strong>DIRECCIÓN MATRIZ:</strong> <?= htmlspecialchars($factura['direccion_matriz']) ?></p>
                <p><strong>DIRECCIÓN ESTABLECIMIENTO:</strong> <?= htmlspecialchars($factura['direccion_establecimiento']) ?></p>
            </div>
            <div class="invoice-info">
                <h2>Identificación del Comprobante</h2>
                <p><strong>NRO:</strong> <?= htmlspecialchars($factura['numero_factura']) ?></p>
                <p><strong>FECHA DE EMISIÓN:</strong> <?= date('d/m/Y', strtotime($factura['fecha_emision'])) ?></p>
                <p><strong>CLAVE DE ACCESO:</strong> <?= htmlspecialchars($factura['clave_acceso']) ?></p>
                <?php if ($factura['guia_remision']): ?>
                    <p><strong>GUÍA DE REMISIÓN:</strong> <?= htmlspecialchars($factura['guia_remision']) ?></p>
                <?php endif; ?>
                <p><strong>MONEDA:</strong> <?= $factura['moneda'] == 'USD' ? 'Dólares americanos ($)' : ($factura['moneda'] == 'EUR' ? 'Euros (€)' : 'Moneda local') ?></p>
            </div>
        </div>
        
        <div class="section">
            <h2>Datos del Comprador</h2>
            <p><strong><?= getTipoIdentificacion($factura['cliente_tipo_identificacion']) ?>:</strong> 
               <?= htmlspecialchars($factura['cliente_identificacion']) ?></p>
            <p><strong>RAZÓN SOCIAL/APELLIDOS Y NOMBRES:</strong> <?= htmlspecialchars($factura['cliente_nombre']) ?></p>
            <p><strong>DIRECCIÓN COMPRADOR:</strong> <?= htmlspecialchars($factura['cliente_direccion']) ?></p>
        </div>
        
        <div class="section">
            <h2>Detalle de la Factura</h2>
            <table>
                <thead>
                    <tr>
                        <th>Cantidad</th>
                        <th>Código</th>
                        <th>Código Auxiliar</th>
                        <th>Descripción</th>
                        <th>P. Unitario</th>
                        <th>Subsidio</th>
                        <th>P. Sin Subsidio</th>
                        <th>Descuento</th>
                        <th>Valor Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $detalle): ?>
                        <tr>
                            <td><?= htmlspecialchars($detalle['cantidad']) ?></td>
                            <td><?= htmlspecialchars($detalle['codigo']) ?></td>
                            <td><?= htmlspecialchars($detalle['codigo_auxiliar']) ?></td>
                            <td><?= htmlspecialchars($detalle['descripcion']) ?></td>
                            <td><?= formatMoney($detalle['precio_unitario'], $factura['moneda']) ?></td>
                            <td><?= formatMoney($detalle['subsidio'], $factura['moneda']) ?></td>
                            <td><?= formatMoney($detalle['precio_sin_subsidio'], $factura['moneda']) ?></td>
                            <td><?= htmlspecialchars($detalle['descuento']) ?>%</td>
                            <td><?= formatMoney($detalle['subtotal'], $factura['moneda']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (!empty($formas_pago)): ?>
        <div class="section">
            <h2>Formas de Pago</h2>
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Valor</th>
                        <th>Plazo (días)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($formas_pago as $forma): ?>
                        <tr>
                            <td><?= htmlspecialchars($forma['codigo']) ?></td>
                            <td><?= htmlspecialchars($forma['descripcion']) ?></td>
                            <td><?= formatMoney($forma['valor'], $factura['moneda']) ?></td>
                            <td><?= htmlspecialchars($forma['plazo']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="totals">
            <div>SUBTOTAL SIN IMPUESTOS: <?= formatMoney($factura['subtotal_0'] + $factura['subtotal_12'] + $factura['subtotal_5'] + $factura['subtotal_tarifa_especial'], $factura['moneda']) ?></div>
            <?php if ($factura['subtotal_12'] > 0): ?>
                <div>SUBTOTAL 12%: <?= formatMoney($factura['subtotal_12'], $factura['moneda']) ?></div>
            <?php endif; ?>
            <?php if ($factura['subtotal_5'] > 0): ?>
                <div>SUBTOTAL 5%: <?= formatMoney($factura['subtotal_5'], $factura['moneda']) ?></div>
            <?php endif; ?>
            <?php if ($factura['subtotal_tarifa_especial'] > 0): ?>
                <div>SUBTOTAL TARIFA ESPECIAL: <?= formatMoney($factura['subtotal_tarifa_especial'], $factura['moneda']) ?></div>
            <?php endif; ?>
            <?php if ($factura['subtotal_no_objeto'] > 0): ?>
                <div>SUBTOTAL NO OBJETO DE IVA: <?= formatMoney($factura['subtotal_no_objeto'], $factura['moneda']) ?></div>
            <?php endif; ?>
            <?php if ($factura['subtotal_exento'] > 0): ?>
                <div>SUBTOTAL EXENTO DE IVA: <?= formatMoney($factura['subtotal_exento'], $factura['moneda']) ?></div>
            <?php endif; ?>
            <?php if ($factura['descuentos'] > 0): ?>
                <div>TOTAL DESCUENTO: <?= formatMoney($factura['descuentos'], $factura['moneda']) ?></div>
            <?php endif; ?>
            <?php if ($factura['ice'] > 0): ?>
                <div>VALOR ICE: <?= formatMoney($factura['ice'], $factura['moneda']) ?></div>
            <?php endif; ?>
            <?php if ($factura['iva_12'] > 0): ?>
                <div>IVA 12%: <?= formatMoney($factura['iva_12'], $factura['moneda']) ?></div>
            <?php endif; ?>
            <?php if ($factura['iva_5'] > 0): ?>
                <div>IVA 5%: <?= formatMoney($factura['iva_5'], $factura['moneda']) ?></div>
            <?php endif; ?>
            <?php if ($factura['iva_tarifa_especial'] > 0): ?>
                <div>IVA TARIFA ESPECIAL: <?= formatMoney($factura['iva_tarifa_especial'], $factura['moneda']) ?></div>
            <?php endif; ?>
            <?php if ($factura['irbpnr'] > 0): ?>
                <div>VALOR IRBPNR: <?= formatMoney($factura['irbpnr'], $factura['moneda']) ?></div>
            <?php endif; ?>
            <?php if ($factura['propina'] > 0): ?>
                <div>PROPINA 10%: <?= formatMoney($factura['propina'], $factura['moneda']) ?></div>
            <?php endif; ?>
            <div class="total-grande">VALOR TOTAL: <?= formatMoney($factura['total'], $factura['moneda']) ?></div>
        </div>
        
        <?php if (!empty($datos_adicionales)): ?>
        <div class="section">
            <h2>Datos Adicionales</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos_adicionales as $dato): ?>
                        <tr>
                            <td><?= htmlspecialchars($dato['nombre']) ?></td>
                            <td><?= htmlspecialchars($dato['descripcion']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($factura['observaciones'])): ?>
        <div class="section">
            <h2>Observaciones</h2>
            <p><?= nl2br(htmlspecialchars($factura['observaciones'])) ?></p>
        </div>
        <?php endif; ?>
        
        <div class="actions no-print">
            <a href="javascript:window.print()" class="btn">Imprimir Comprobante</a>
            <a href="menu_principal.php" class="btn btn-secondary">Volver al Menú</a>
        </div>
    </div>
</body>
</html>