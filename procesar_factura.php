<?php
require_once './php/config.php';
require 'vendor/autoload.php';

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

session_start();
$empresa_id = $_SESSION['empresa_id'] ?? null;

if (!$empresa_id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 1. Obtener datos del formulario
$cliente_id = intval($_POST['cliente_id']);
$fecha_emision = limpiarDato($_POST['fecha_emision']);
$tipo_comprobante = limpiarDato($_POST['tipo_comprobante']);
$observaciones = limpiarDato($_POST['observaciones']);
$items = $_POST['items'] ?? [];

// 2. Validar datos
if (empty($items)) {
    $_SESSION['error_factura'] = 'Debe agregar al menos un producto a la factura';
    header('Location: facturacion.php');
    exit;
}

// 3. Calcular totales
$subtotal_0 = 0;
$subtotal_15 = 0;
$iva = 0;

foreach ($items as $item) {
    $cantidad = floatval($item['cantidad']);
    $precio = floatval($item['precio']);
    $descuento = floatval($item['descuento']);
    $iva_item = isset($item['iva']) && $item['iva'] == 1 ? 0.15 : 0;
    
    $subtotal = $cantidad * $precio * (1 - $descuento/100);
    
    if ($iva_item > 0) {
        $subtotal_15 += $subtotal;
        $iva += $subtotal * $iva_item;
    } else {
        $subtotal_0 += $subtotal;
    }
}

$total = $subtotal_0 + $subtotal_15 + $iva;

// 4. Generar número de factura (secuencial según SRI)
$conexion = conectarDB();

// Obtener datos de la empresa
$sql_empresa = "SELECT * FROM empresas WHERE id = ?";
$stmt_empresa = $conexion->prepare($sql_empresa);
$stmt_empresa->bind_param("i", $empresa_id);
$stmt_empresa->execute();
$empresa = $stmt_empresa->get_result()->fetch_assoc();
$stmt_empresa->close();

// Formato: 001-002-000000001 (Establecimiento-PuntoEmisión-Secuencial)
$secuencial = str_pad($empresa['ultimo_secuencial'] + 1, 9, '0', STR_PAD_LEFT);
$numero_factura = $empresa['codigo_establecimiento'] . '-' . $empresa['codigo_punto_emision'] . '-' . $secuencial;

// 5. Guardar en base de datos
$conexion->begin_transaction();

try {
    // Insertar factura
    $sql_factura = "INSERT INTO facturas (
        empresa_id, cliente_id, numero_factura, fecha_emision, tipo_comprobante,
        subtotal_0, subtotal_15, iva, total, observaciones, estado
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDIENTE')";
    
    $stmt_factura = $conexion->prepare($sql_factura);
    $stmt_factura->bind_param(
        "iisssddddss",
        $empresa_id,
        $cliente_id,
        $numero_factura,
        $fecha_emision,
        $tipo_comprobante,
        $subtotal_0,
        $subtotal_15,
        $iva,
        $total,
        $observaciones
    );
    
    $stmt_factura->execute();
    $factura_id = $conexion->insert_id;
    $stmt_factura->close();
    
    // Insertar detalles
    $sql_detalle = "INSERT INTO factura_detalles (
        factura_id, producto_id, codigo, descripcion, cantidad, 
        precio_unitario, descuento, subtotal, iva
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_detalle = $conexion->prepare($sql_detalle);
    
    foreach ($items as $item) {
        $producto_id = !empty($item['producto_id']) ? intval($item['producto_id']) : null;
        $codigo = limpiarDato($item['codigo']);
        $descripcion = limpiarDato($item['descripcion']);
        $cantidad = floatval($item['cantidad']);
        $precio = floatval($item['precio']);
        $descuento = floatval($item['descuento']);
        $subtotal = $cantidad * $precio * (1 - $descuento/100);
        $iva_item = isset($item['iva']) && $item['iva'] == 1 ? $subtotal * 0.15 : 0;
        
        $stmt_detalle->bind_param(
            "iissddddd",
            $factura_id,
            $producto_id,
            $codigo,
            $descripcion,
            $cantidad,
            $precio,
            $descuento,
            $subtotal,
            $iva_item
        );
        
        $stmt_detalle->execute();
    }
    
    $stmt_detalle->close();
    
    // Actualizar secuencial
    $sql_secuencial = "UPDATE empresas SET ultimo_secuencial = ultimo_secuencial + 1 WHERE id = ?";
    $stmt_secuencial = $conexion->prepare($sql_secuencial);
    $stmt_secuencial->bind_param("i", $empresa_id);
    $stmt_secuencial->execute();
    $stmt_secuencial->close();
    
    $conexion->commit();
} catch (Exception $e) {
    $conexion->rollback();
    $_SESSION['error_factura'] = 'Error al guardar la factura: ' . $e->getMessage();
    header('Location: facturacion.php');
    exit;
}

// 6. Generar XML según formato del SRI
$xml = new DOMDocument('1.0', 'UTF-8');

// Crear elemento raíz
$factura = $xml->createElement('factura');
$factura->setAttribute('id', 'comprobante');
$factura->setAttribute('version', '1.0.0');
$xml->appendChild($factura);

// InfoTributaria
$infoTributaria = $xml->createElement('infoTributaria');
$factura->appendChild($infoTributaria);

$infoTributaria->appendChild($xml->createElement('ambiente', $empresa['tipo_ambiente'] === 'PRUEBAS' ? '1' : '2'));
$infoTributaria->appendChild($xml->createElement('tipoEmision', '1'));
$infoTributaria->appendChild($xml->createElement('razonSocial', $empresa['razon_social']));
$infoTributaria->appendChild($xml->createElement('nombreComercial', $empresa['nombre_comercial']));
$infoTributaria->appendChild($xml->createElement('ruc', $empresa['ruc']));
$infoTributaria->appendChild($xml->createElement('codDoc', '01')); // 01=Factura
$infoTributaria->appendChild($xml->createElement('estab', $empresa['codigo_establecimiento']));
$infoTributaria->appendChild($xml->createElement('ptoEmi', $empresa['codigo_punto_emision']));
$infoTributaria->appendChild($xml->createElement('secuencial', $secuencial));
$infoTributaria->appendChild($xml->createElement('dirMatriz', $empresa['direccion_matriz']));

// InfoFactura
$infoFactura = $xml->createElement('infoFactura');
$factura->appendChild($infoFactura);

$infoFactura->appendChild($xml->createElement('fechaEmision', date('d/m/Y', strtotime($fecha_emision))));
$infoFactura->appendChild($xml->createElement('dirEstablecimiento', $empresa['direccion_establecimiento']));
$infoFactura->appendChild($xml->createElement('obligadoContabilidad', $empresa['obligado_contabilidad']));
$infoFactura->appendChild($xml->createElement('tipoIdentificacionComprador', '04')); // 04=RUC
$infoFactura->appendChild($xml->createElement('razonSocialComprador', $factura['cliente_nombre']));
$infoFactura->appendChild($xml->createElement('identificacionComprador', $factura['identificacion']));
$infoFactura->appendChild($xml->createElement('totalSinImpuestos', number_format($subtotal_0 + $subtotal_15, 2, '.', '')));
$infoFactura->appendChild($xml->createElement('totalDescuento', '0.00'));

// Total con impuestos
$totalConImpuestos = $xml->createElement('totalConImpuestos');
$infoFactura->appendChild($totalConImpuestos);

$totalImpuesto = $xml->createElement('totalImpuesto');
$totalImpuesto->appendChild($xml->createElement('codigo', '2')); // 2=IVA
$totalImpuesto->appendChild($xml->createElement('codigoPorcentaje', '2')); // 2=15%
$totalImpuesto->appendChild($xml->createElement('baseImponible', number_format($subtotal_15, 2, '.', '')));
$totalImpuesto->appendChild($xml->createElement('valor', number_format($iva, 2, '.', '')));
$totalConImpuestos->appendChild($totalImpuesto);

$infoFactura->appendChild($xml->createElement('propina', '0.00'));
$infoFactura->appendChild($xml->createElement('importeTotal', number_format($total, 2, '.', '')));
$infoFactura->appendChild($xml->createElement('moneda', 'DOLAR'));

// Detalles
$detalles = $xml->createElement('detalles');
$factura->appendChild($detalles);

foreach ($items as $item) {
    $detalle = $xml->createElement('detalle');
    
    $detalle->appendChild($xml->createElement('codigoPrincipal', $item['codigo']));
    $detalle->appendChild($xml->createElement('descripcion', $item['descripcion']));
    $detalle->appendChild($xml->createElement('cantidad', number_format($item['cantidad'], 2, '.', '')));
    $detalle->appendChild($xml->createElement('precioUnitario', number_format($item['precio'], 2, '.', '')));
    $detalle->appendChild($xml->createElement('descuento', number_format(0, 2, '.', '')));
    $detalle->appendChild($xml->createElement('precioTotalSinImpuesto', number_format($item['cantidad'] * $item['precio'], 2, '.', '')));
    
    $impuestos = $xml->createElement('impuestos');
    $detalle->appendChild($impuestos);
    
    if (isset($item['iva']) && $item['iva'] == 1) {
        $impuesto = $xml->createElement('impuesto');
        $impuesto->appendChild($xml->createElement('codigo', '2')); // 2=IVA
        $impuesto->appendChild($xml->createElement('codigoPorcentaje', '2')); // 2=15%
        $impuesto->appendChild($xml->createElement('tarifa', '15.00'));
        $impuesto->appendChild($xml->createElement('baseImponible', number_format($item['cantidad'] * $item['precio'], 2, '.', '')));
        $impuesto->appendChild($xml->createElement('valor', number_format($item['cantidad'] * $item['precio'] * 0.15, 2, '.', '')));
        $impuestos->appendChild($impuesto);
    }
    
    $detalles->appendChild($detalle);
}

// Guardar XML temporal
$xml_temp_file = "temp_factura_" . $factura_id . ".xml";
$xml->save($xml_temp_file);

// 7. Firmar el XML
$certFile = "cert.pem";  // Certificado extraído del .p12
$keyFile = "clave.pem";  // Clave privada extraída del .p12

// Crear la firma digital
$objDSig = new XMLSecurityDSig();
$objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
$objDSig->addReference(
    $xml,
    XMLSecurityDSig::SHA1,
    ['http://www.w3.org/2000/09/xmldsig#enveloped-signature']
);

// Cargar clave privada
$objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'private']);
$objKey->loadKey($keyFile, true);

// Firmar el XML
$objDSig->sign($objKey);
$objDSig->add509Cert(file_get_contents($certFile));
$objDSig->appendSignature($xml->documentElement);

// Guardar XML firmado
$xml_firmado_file = "facturas/factura_" . $factura_id . ".xml";
$xml->save($xml_firmado_file);

// 8. Enviar al SRI (modo prueba)
$wsdl = $empresa['tipo_ambiente'] === 'PRUEBAS' ? 
    "https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl" :
    "https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl";

$client = new SoapClient($wsdl, [
    "trace" => 1,
    "exception" => 0,
    "stream_context" => stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ])
]);

$xml_firmado = file_get_contents($xml_firmado_file);
$xml_base64 = base64_encode($xml_firmado);

try {
    $response = $client->validarComprobante(["xml" => $xml_base64]);
    
    // Actualizar estado de la factura
    $estado = 'RECHAZADO';
    $clave_acceso = '';
    $numero_autorizacion = '';
    $fecha_autorizacion = null;
    
    if (isset($response->RespuestaRecepcionComprobante->comprobantes->comprobante->mensajes)) {
        $mensaje = $response->RespuestaRecepcionComprobante->comprobantes->comprobante->mensajes->mensaje->mensaje;
        $estado = strpos($mensaje, 'AUTORIZADO') !== false ? 'AUTORIZADO' : 'RECHAZADO';
        
        if ($estado === 'AUTORIZADO') {
            $clave_acceso = $response->RespuestaRecepcionComprobante->comprobantes->comprobante->claveAcceso;
            $numero_autorizacion = $response->RespuestaRecepcionComprobante->comprobantes->comprobante->numeroAutorizacion;
            $fecha_autorizacion = date('Y-m-d H:i:s', strtotime($response->RespuestaRecepcionComprobante->comprobantes->comprobante->fechaAutorizacion));
        }
    }
    
    $sql_update = "UPDATE facturas SET 
        estado = ?,
        clave_acceso = ?,
        numero_autorizacion = ?,
        fecha_autorizacion = ?
        WHERE id = ?";
    
    $stmt_update = $conexion->prepare($sql_update);
    $stmt_update->bind_param(
        "ssssi",
        $estado,
        $clave_acceso,
        $numero_autorizacion,
        $fecha_autorizacion,
        $factura_id
    );
    
    $stmt_update->execute();
    $stmt_update->close();
    
    // Eliminar archivo temporal
    unlink($xml_temp_file);
    
    $_SESSION['factura_procesada'] = true;
    header('Location: comprobante.php');
    exit;
} catch (Exception $e) {
    // En caso de error, marcar como rechazado
    $sql_update = "UPDATE facturas SET estado = 'RECHAZADO' WHERE id = ?";
    $stmt_update = $conexion->prepare($sql_update);
    $stmt_update->bind_param("i", $factura_id);
    $stmt_update->execute();
    $stmt_update->close();
    
    $_SESSION['error_factura'] = 'Error al enviar al SRI: ' . $e->getMessage();
    header('Location: facturacion.php');
    exit;
}
?>