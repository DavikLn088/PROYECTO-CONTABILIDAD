<?php
$empresa = [
    'ruc' => '',
    'razon_social' => '',
    'nombre_comercial' => '',
    'direccion_matriz' => '',
    'direccion_establecimiento' => '',
    'codigo_establecimiento' => '',
    'codigo_punto_emision' => '',
    'obligado_contabilidad' => '',
    'contribuyente_rimpe' => '',
    'logo' => '',
    'tipo_ambiente' => 'PRUEBAS',
    'token_firma' => 'SD - ePass3003 auto'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso de Datos</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { width: 50%; margin: auto; border: 1px solid #000; padding: 20px; }
        .form-group { margin-bottom: 10px; }
        label { display: block; font-weight: bold; }
        input { width: 100%; padding: 8px; }
        button { padding: 10px; background-color: blue; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Ingreso de Datos de la Empresa</h2>
        <form action="procesar.php" method="POST">
            <div class="form-group">
                <label for="ruc">RUC:</label>
                <input type="text" name="ruc" required>
            </div>
            <div class="form-group">
                <label for="razon_social">Razón Social:</label>
                <input type="text" name="razon_social" required>
            </div>
            <div class="form-group">
                <label for="nombre_comercial">Nombre Comercial:</label>
                <input type="text" name="nombre_comercial">
            </div>
            <div class="form-group">
                <label for="direccion_matriz">Dirección Matriz:</label>
                <input type="text" name="direccion_matriz">
            </div>
            <div class="form-group">
                <label for="direccion_establecimiento">Dirección Establecimiento:</label>
                <input type="text" name="direccion_establecimiento">
            </div>
            <div class="form-group">
                <label for="codigo_establecimiento">Código Establecimiento:</label>
                <input type="text" name="codigo_establecimiento">
            </div>
            <div class="form-group">
                <label for="codigo_punto_emision">Código del punto de Emisión:</label>
                <input type="text" name="codigo_punto_emision">
            </div>
            <div class="form-group">
                <label for="obligado_contabilidad">Obligado a llevar contabilidad:</label>
                <input type="checkbox" name="obligado_contabilidad" value="SI">
            </div>
            <div class="form-group">
                <label for="contribuyente_rimpe">Contribuyente RIMPE:</label>
                <input type="checkbox" name="contribuyente_rimpe" value="SI">
            </div>
            <div class="form-group">
                <label for="logo">Logo (Imagen):</label>
                <input type="file" name="logo">
            </div>
            <div class="form-group">
                <label for="tipo_ambiente">Tipo de ambiente:</label>
                <input type="text" name="tipo_ambiente" value="PRUEBAS" readonly>
            </div>
            <div class="form-group">
                <label for="token_firma">Token para firmar:</label>
                <input type="text" name="token_firma" value="SD - ePass3003 auto" readonly>
            </div>
            <button type="submit">Guardar</button>
        </form>
    </div>
</body>
</html>
