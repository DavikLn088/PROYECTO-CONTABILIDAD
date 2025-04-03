<?php
require_once './php/config.php';

session_start();

// Verificar parámetro ID y sesión
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_SESSION['empresa_id'])) {
    $_SESSION['error_message'] = 'Acceso no autorizado';
    header('Location: listar_facturas.php');
    exit;
}

$factura_id = intval($_GET['id']);
$empresa_id = intval($_SESSION['empresa_id']);

try {
    // Verificar que la factura pertenece a la empresa
    $conexion = conectarDB();
    
    // Usar consultas preparadas con PDO
    $sql = "SELECT f.numero_factura FROM facturas f 
            WHERE f.id = :factura_id AND f.empresa_id = :empresa_id";
    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':factura_id', $factura_id, PDO::PARAM_INT);
    $stmt->bindParam(':empresa_id', $empresa_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $factura = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$factura) {
        throw new Exception("Factura no encontrada o no pertenece a esta empresa");
    }

    // Ruta segura del archivo XML
    $xml_dir = __DIR__ . '/facturas/';
    $xml_file = $xml_dir . 'factura_' . $factura_id . '.xml';

    // Validar existencia del archivo
    if (!file_exists($xml_file)) {
        throw new Exception("El archivo XML no existe para esta factura");
    }

    // Validar que es un archivo XML (seguridad adicional)
    $file_info = new finfo(FILEINFO_MIME);
    $mime_type = $file_info->file($xml_file);
    
    if (strpos($mime_type, 'xml') === false) {
        throw new Exception("El archivo no es un XML válido");
    }

    // Configurar headers para descarga
    header('Content-Description: File Transfer');
    header('Content-Type: application/xml');
    header('Content-Disposition: attachment; filename="factura_' . htmlspecialchars($factura['numero_factura']) . '.xml"');
    header('Content-Length: ' . filesize($xml_file));
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('X-Content-Type-Options: nosniff');

    // Limpiar el buffer de salida y enviar el archivo
    ob_clean();
    flush();
    readfile($xml_file);
    exit;

} catch (Exception $e) {
    // Registrar el error y redirigir
    error_log('Error al descargar XML: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Error al descargar el archivo XML: ' . $e->getMessage();
    
    if (isset($conexion)) {
        $conexion = null; // Cerrar conexión si está abierta
    }
    
    header('Location: listar_facturas.php');
    exit;
}