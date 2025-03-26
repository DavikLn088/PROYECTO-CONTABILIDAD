<?php
require_once './php/config.php';

session_start();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listar_facturas.php');
    exit;
}

$factura_id = intval($_GET['id']);
$empresa_id = $_SESSION['empresa_id'] ?? null;

// Verificar que la factura pertenece a la empresa
$conexion = conectarDB();
$sql = "SELECT f.* FROM facturas f 
        WHERE f.id = ? AND f.empresa_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $factura_id, $empresa_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conexion->close();
    header('Location: listar_facturas.php');
    exit;
}

$factura = $result->fetch_assoc();
$stmt->close();
$conexion->close();

// Ruta del archivo XML
$xml_file = "facturas/factura_" . $factura_id . ".xml";

if (!file_exists($xml_file)) {
    die("El archivo XML no existe");
}

// Configurar headers para descarga
header('Content-Description: File Transfer');
header('Content-Type: application/xml');
header('Content-Disposition: attachment; filename="' . basename($factura['numero_factura'] . '.xml"') .'');
header('Content-Length: ' . filesize($xml_file));
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Leer el archivo y enviarlo al navegador
readfile($xml_file);
exit;
?>