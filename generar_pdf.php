<?php
require_once './php/config.php';
require_once 'tcpdf/tcpdf.php'; // Asegúrate de tener TCPDF instalado

session_start();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listar_facturas.php');
    exit;
}

$factura_id = intval($_GET['id']);
$empresa_id = $_SESSION['empresa_id'] ?? null;

// Obtener datos de la factura
$conexion = conectarDB();
$sql = "SELECT f.*, c.*, e.* 
        FROM facturas f
        JOIN clientes c ON f.cliente_id = c.id
        JOIN empresas e ON f.empresa_id = e.id
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

// Obtener detalles de la factura
$sql_detalles = "SELECT * FROM factura_detalles WHERE factura_id = ?";
$stmt_detalles = $conexion->prepare($sql_detalles);
$stmt_detalles->bind_param("i", $factura_id);
$stmt_detalles->execute();
$detalles = $stmt_detalles->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_detalles->close();
$conexion->close();

// Crear PDF
class MYPDF extends TCPDF {
    // Cabecera de página
    public function Header() {
        // Logo de la empresa (si existe)
        $logo_file = 'uploads/' . basename($this->empresa_logo);
        if (!empty($this->empresa_logo) && file_exists($logo_file)) {
            $this->Image($logo_file, 10, 10, 30, 0, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
        } else {
            // Mostrar "NO TIENE LOGO" si no hay imagen
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(0, 10, 'NO TIENE LOGO', 0, 1, 'R');
        }
        
        // Información de la empresa
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(0, 5, strtoupper($this->empresa_razon_social), 0, 1, 'R');
        $this->SetFont('helvetica', '', 9);
        $this->Cell(0, 5, strtoupper($this->empresa_nombre_comercial), 0, 1, 'R');
        
        // Direcciones
        $this->Cell(0, 5, 'Dirección Matriz: ' . strtoupper($this->empresa_direccion_matriz), 0, 1, 'R');
        $this->Cell(0, 5, 'Dirección Establecimiento: ' . strtoupper($this->empresa_direccion_establecimiento), 0, 1, 'R');
        
        // Indicadores especiales
        $indicadores = [];
        if ($this->empresa_exportador_bienes === 'SI') $indicadores[] = 'EXPORTADOR HABITUAL DE BIENES';
        if ($this->empresa_obligado_contabilidad === 'SI') $indicadores[] = 'OBLIGADO A LLEVAR CONTABILIDAD';
        if (!empty($indicadores)) {
            $this->Cell(0, 5, implode(' | ', $indicadores), 0, 1, 'R');
        }
        
        // RUC y tipo de documento
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(0, 5, 'R.U.C.: ' . $this->empresa_ruc, 0, 1, 'R');
        
        // Tipo de documento
        $tipo_doc = 'FACTURA';
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 10, $tipo_doc, 0, 1, 'C');
        
        // Número de factura
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 5, 'No. ' . $this->factura_numero, 0, 1, 'C');
        
        // Línea separadora
        $this->Line(10, $this->GetY(), $this->getPageWidth()-10, $this->GetY());
        $this->Ln(5);
    }
    
    // Pie de página
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Crear instancia de PDF
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurar propiedades del documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($factura['razon_social']);
$pdf->SetTitle('Factura ' . $factura['numero_factura']);
$pdf->SetSubject('Factura Electrónica');
$pdf->SetKeywords('Factura, SRI, Ecuador');

// Pasar datos a la clase PDF
$pdf->empresa_logo = $factura['logo'];
$pdf->empresa_razon_social = $factura['razon_social'];
$pdf->empresa_nombre_comercial = $factura['nombre_comercial'];
$pdf->empresa_direccion_matriz = $factura['direccion_matriz'];
$pdf->empresa_direccion_establecimiento = $factura['direccion_establecimiento'];
$pdf->empresa_exportador_bienes = $factura['exportador_bienes'];
$pdf->empresa_obligado_contabilidad = $factura['obligado_contabilidad'];
$pdf->empresa_ruc = $factura['ruc'];
$pdf->factura_numero = $factura['numero_factura'];

// Configurar márgenes
$pdf->SetMargins(10, 50, 10);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);

// Añadir página
$pdf->AddPage();

// Información de autorización
if (!empty($factura['numero_autorizacion'])) {
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 5, 'NÚMERO DE AUTORIZACIÓN', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, $factura['numero_autorizacion'], 0, 1, 'L');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 5, 'FECHA Y HORA DE AUTORIZACIÓN:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, date('d/m/Y H:i:s', strtotime($factura['fecha_autorizacion'])), 0, 1, 'L');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 5, 'AMBIENTE:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, strtoupper($factura['tipo_ambiente']), 0, 1, 'L');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 5, 'EMISIÓN:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'NORMAL', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 5, 'CLAVE DE ACCESO:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, $factura['clave_acceso'], 0, 1, 'L');
    
    $pdf->Ln(5);
}

// Información del cliente
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 5, 'Razón Social / Nombres y Apellidos: ' . strtoupper($factura['nombre']), 0, 1, 'L');
$pdf->Cell(0, 5, 'Identificación: ' . $factura['identificacion'], 0, 1, 'L');
$pdf->Cell(0, 5, 'Fecha: ' . date('d/m/Y', strtotime($factura['fecha_emision'])), 0, 1, 'L');
$pdf->Cell(0, 5, 'Dirección: ' . strtoupper($factura['direccion']), 0, 1, 'L');
$pdf->Ln(3);

// Tabla de detalles
$pdf->SetFont('helvetica', '', 8);
$header = array('Cod. Principal', 'Cod. Auxiliar', 'Cantidad', 'Descripción', 'Detalle Adicional', 'Precio Unitario', 'Descuento', 'Precio Total');
$widths = array(20, 20, 15, 50, 40, 20, 20, 20);

// Cabecera de la tabla
$pdf->SetFillColor(220, 220, 220);
foreach ($header as $i => $col) {
    $pdf->Cell($widths[$i], 7, $col, 1, 0, 'C', 1);
}
$pdf->Ln();

// Detalles de los productos
$pdf->SetFillColor(255, 255, 255);
foreach ($detalles as $detalle) {
    $pdf->Cell($widths[0], 6, $detalle['codigo'], 'LR', 0, 'C');
    $pdf->Cell($widths[1], 6, '', 'LR', 0, 'C'); // Cod. Auxiliar (vacío si no se usa)
    $pdf->Cell($widths[2], 6, number_format($detalle['cantidad'], 2), 'LR', 0, 'R');
    $pdf->Cell($widths[3], 6, $detalle['descripcion'], 'LR', 0, 'L');
    $pdf->Cell($widths[4], 6, '', 'LR', 0, 'L'); // Detalle adicional (vacío)
    $pdf->Cell($widths[5], 6, number_format($detalle['precio_unitario'], 2), 'LR', 0, 'R');
    $pdf->Cell($widths[6], 6, number_format($detalle['descuento'], 2), 'LR', 0, 'R');
    $pdf->Cell($widths[7], 6, number_format($detalle['subtotal'], 2), 'LR', 0, 'R');
    $pdf->Ln();
}

// Cierre de la tabla
$pdf->Cell(array_sum($widths), 0, '', 'T');
$pdf->Ln(5);

// Información adicional si existe
if (!empty($factura['observaciones'])) {
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 5, 'Información Adicional', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, $factura['observaciones'], 0, 'L');
    $pdf->Ln(3);
}

// Totales
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(40, 5, 'Forma de pago', 0, 0, 'L');
$pdf->Cell(30, 5, 'Valor', 0, 1, 'R');
$pdf->Cell(40, 5, '20 - OTROS CON UTILIZACION DEL SISTEMA FINANCIERO', 0, 0, 'L');
$pdf->Cell(30, 5, number_format($factura['total'], 2), 0, 1, 'R');
$pdf->Ln(2);

// Resumen de impuestos
$pdf->Cell(40, 5, 'SUBTOTAL 0%', 0, 0, 'L');
$pdf->Cell(30, 5, number_format($factura['subtotal_0'], 2), 0, 1, 'R');

$pdf->Cell(40, 5, 'SUBTOTAL NO OBJETO DE IVA', 0, 0, 'L');
$pdf->Cell(30, 5, '0.00', 0, 1, 'R');

$pdf->Cell(40, 5, 'SUBTOTAL EXENTO DE IVA', 0, 0, 'L');
$pdf->Cell(30, 5, '0.00', 0, 1, 'R');

$pdf->Cell(40, 5, 'SUBTOTAL SIN IMPUESTOS', 0, 0, 'L');
$pdf->Cell(30, 5, number_format($factura['subtotal_0'] + $factura['subtotal_15'], 2), 0, 1, 'R');

$pdf->Cell(40, 5, 'TOTAL DESCUENTO', 0, 0, 'L');
$pdf->Cell(30, 5, '0.00', 0, 1, 'R');

$pdf->Cell(40, 5, 'ICE', 0, 0, 'L');
$pdf->Cell(30, 5, '0.00', 0, 1, 'R');

$pdf->Cell(40, 5, 'TOTAL DEVOLUCION IVA', 0, 0, 'L');
$pdf->Cell(30, 5, '0.00', 0, 1, 'R');

$pdf->Cell(40, 5, 'IRBPNR', 0, 0, 'L');
$pdf->Cell(30, 5, '0.00', 0, 1, 'R');

$pdf->Cell(40, 5, 'PROPINA', 0, 0, 'L');
$pdf->Cell(30, 5, '0.00', 0, 1, 'R');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 5, 'VALOR TOTAL', 0, 0, 'L');
$pdf->Cell(30, 5, number_format($factura['total'], 2), 0, 1, 'R');

$pdf->Cell(40, 5, 'VALOR TOTAL SIN SUBSIDIO', 0, 0, 'L');
$pdf->Cell(30, 5, '0.00', 0, 1, 'R');

$pdf->Cell(40, 5, 'AHORRO POR SUBSIDIO: (Incluye IVA cuando corresponda)', 0, 0, 'L');
$pdf->Cell(30, 5, '0.00', 0, 1, 'R');

// Generar PDF
$pdf->Output('factura_' . $factura['numero_factura'] . '.pdf', 'I');
?>