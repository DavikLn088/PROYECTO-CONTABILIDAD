<?php
// procesar.php

require_once './php/config.php';

// Verificar que el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Función para limpiar datos
function limpiarDato($dato) {
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato);
    return $dato;
}

// Inicializar array para almacenar datos limpios
$datosEmpresa = [];

// Procesar campos de texto
$camposTexto = [
    'ruc', 'razon_social', 'nombre_comercial', 'direccion_matriz',
    'direccion_establecimiento', 'codigo_establecimiento', 
    'codigo_punto_emision', 'contribuyente_especial', 'agente_de_retencion',
    'tipo_ambiente', 'token_firma'
];

foreach ($camposTexto as $campo) {
    $datosEmpresa[$campo] = isset($_POST[$campo]) ? limpiarDato($_POST[$campo]) : '';
}

// Procesar checkboxes (establecer 'NO' si no están marcados)
$checkboxes = [
    'obligado_contabilidad' => 'NO',
    'exportador_bienes' => 'NO',
    'contribuyente_rimpe' => 'NO'
];

foreach ($checkboxes as $checkbox => $valorDefault) {
    $datosEmpresa[$checkbox] = isset($_POST[$checkbox]) ? 'SI' : $valorDefault;
}

// Validación básica de campos requeridos
$errores = [];

if (empty($datosEmpresa['ruc'])) {
    $errores[] = 'El RUC es obligatorio';
} elseif (!preg_match('/^[0-9]{13}$/', $datosEmpresa['ruc'])) {
    $errores[] = 'El RUC debe tener 13 dígitos';
}

if (empty($datosEmpresa['razon_social'])) {
    $errores[] = 'La Razón Social es obligatoria';
}

if (!empty($errores)) {
    session_start();
    $_SESSION['errores'] = $errores;
    $_SESSION['datos'] = $datosEmpresa;
    header('Location: index.php');
    exit;
}

// Procesar archivo de logo (si se subió)
$datosEmpresa['logo'] = null;
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $datosEmpresa['logo'] = subirArchivo($_FILES['logo']);
}

// Conectar a la base de datos y guardar los datos
$conexion = conectarDB();

// Preparar la consulta SQL
$sql = "INSERT INTO empresas (
    ruc, razon_social, nombre_comercial, direccion_matriz,
    direccion_establecimiento, codigo_establecimiento, codigo_punto_emision,
    contribuyente_especial, obligado_contabilidad, exportador_bienes,
    contribuyente_rimpe, agente_de_retencion, logo, tipo_ambiente, token_firma
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conexion->prepare($sql);

if ($stmt === false) {
    die("Error al preparar la consulta: " . $conexion->error);
}

// Vincular parámetros
$stmt->bind_param(
    "sssssssssssssss",
    $datosEmpresa['ruc'],
    $datosEmpresa['razon_social'],
    $datosEmpresa['nombre_comercial'],
    $datosEmpresa['direccion_matriz'],
    $datosEmpresa['direccion_establecimiento'],
    $datosEmpresa['codigo_establecimiento'],
    $datosEmpresa['codigo_punto_emision'],
    $datosEmpresa['contribuyente_especial'],
    $datosEmpresa['obligado_contabilidad'],
    $datosEmpresa['exportador_bienes'],
    $datosEmpresa['contribuyente_rimpe'],
    $datosEmpresa['agente_de_retencion'],
    $datosEmpresa['logo'],
    $datosEmpresa['tipo_ambiente'],
    $datosEmpresa['token_firma']
);

// Ejecutar la consulta
if ($stmt->execute()) {
    $idEmpresa = $stmt->insert_id;
    session_start();
    $_SESSION['empresa_id'] = $idEmpresa;
    header('Location: resultado.php');
} else {
    session_start();
    $_SESSION['errores'] = ['Error al guardar en la base de datos: ' . $stmt->error];
    $_SESSION['datos'] = $datosEmpresa;
    header('Location: index.php');
}

$stmt->close();
$conexion->close();
exit;