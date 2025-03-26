<?php
session_start();
require_once './php/config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = conectarDB();
        
        // Inicializar array de datos con valores por defecto
        $datos = [
            'ruc' => '',
            'razon_social' => '',
            'nombre_comercial' => '',
            'direccion_matriz' => '',
            'direccion_establecimiento' => '',
            'codigo_establecimiento' => '',
            'codigo_punto_emision' => '',
            'contribuyente_especial' => null,
            'obligado_contabilidad' => 'NO',
            'exportador_bienes' => 'NO',
            'contribuyente_rimpe' => 'NO',
            'agente_de_retencion' => null,
            'tipo_ambiente' => 'PRUEBAS',
            'logo' => null,
            'usuario_id' => $_SESSION['usuario_id']
        ];

        // Asignar valores desde $_POST con validación
        foreach ($datos as $key => &$value) {
            if ($key === 'obligado_contabilidad' || $key === 'exportador_bienes' || $key === 'contribuyente_rimpe') {
                $value = isset($_POST[$key]) ? 'SI' : 'NO';
            } elseif ($key === 'usuario_id') {
                continue; // Ya tiene valor
            } elseif (isset($_POST[$key])) {
                $value = htmlspecialchars(trim($_POST[$key]));
            }
        }
        unset($value); // Romper la referencia

        // Validar campos obligatorios
        $camposObligatorios = [
            'ruc' => 'RUC',
            'razon_social' => 'Razón Social',
            'direccion_matriz' => 'Dirección Matriz',
            'codigo_establecimiento' => 'Código Establecimiento',
            'codigo_punto_emision' => 'Código Punto de Emisión',
            'tipo_ambiente' => 'Tipo de Ambiente'
        ];

        foreach ($camposObligatorios as $campo => $nombre) {
            if (empty($datos[$campo])) {
                throw new Exception("El campo $nombre es obligatorio");
            }
        }

        // Validaciones específicas
        if (!preg_match('/^[0-9]{13}$/', $datos['ruc'])) {
            throw new Exception("El RUC debe tener exactamente 13 dígitos");
        }

        if (!preg_match('/^[0-9]{3}$/', $datos['codigo_establecimiento'])) {
            throw new Exception("El Código de Establecimiento debe tener 3 dígitos");
        }

        if (!preg_match('/^[0-9]{3}$/', $datos['codigo_punto_emision'])) {
            throw new Exception("El Código de Punto de Emisión debe tener 3 dígitos");
        }

        // Procesar logo
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $permitidos = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['logo']['type'], $permitidos)) {
                throw new Exception("Solo se permiten imágenes JPEG, PNG o GIF");
            }

            $nombreArchivo = uniqid() . '_' . basename($_FILES['logo']['name']);
            $rutaDestino = './uploads/logos/' . $nombreArchivo;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $rutaDestino)) {
                $datos['logo'] = $rutaDestino;
            } else {
                throw new Exception("Error al subir el archivo de logo");
            }
        }

        // Preparar consulta SQL con todos los parámetros necesarios
        $sql = "INSERT INTO empresas (
            ruc, razon_social, nombre_comercial, direccion_matriz, 
            direccion_establecimiento, codigo_establecimiento, codigo_punto_emision,
            contribuyente_especial, obligado_contabilidad, exportador_bienes,
            contribuyente_rimpe, agente_de_retencion, logo, tipo_ambiente, usuario_id
        ) VALUES (
            :ruc, :razon_social, :nombre_comercial, :direccion_matriz, 
            :direccion_establecimiento, :codigo_establecimiento, :codigo_punto_emision,
            :contribuyente_especial, :obligado_contabilidad, :exportador_bienes,
            :contribuyente_rimpe, :agente_de_retencion, :logo, :tipo_ambiente, :usuario_id
        )";
        
        $stmt = $pdo->prepare($sql);
        
        // Asegurar que todos los parámetros estén definidos
        $parametros = [
            ':ruc' => $datos['ruc'],
            ':razon_social' => $datos['razon_social'],
            ':nombre_comercial' => $datos['nombre_comercial'],
            ':direccion_matriz' => $datos['direccion_matriz'],
            ':direccion_establecimiento' => $datos['direccion_establecimiento'],
            ':codigo_establecimiento' => $datos['codigo_establecimiento'],
            ':codigo_punto_emision' => $datos['codigo_punto_emision'],
            ':contribuyente_especial' => $datos['contribuyente_especial'],
            ':obligado_contabilidad' => $datos['obligado_contabilidad'],
            ':exportador_bienes' => $datos['exportador_bienes'],
            ':contribuyente_rimpe' => $datos['contribuyente_rimpe'],
            ':agente_de_retencion' => $datos['agente_de_retencion'],
            ':logo' => $datos['logo'],
            ':tipo_ambiente' => $datos['tipo_ambiente'],
            ':usuario_id' => $datos['usuario_id']
        ];
        
        // Ejecutar con los parámetros explícitos
        if (!$stmt->execute($parametros)) {
            throw new Exception("Error al ejecutar la consulta SQL");
        }
        
        $_SESSION['success_message'] = 'Empresa creada correctamente';
        header('Location: menu_principal.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        // Opcional: registrar el error en un log
        // error_log('Error en nueva_empresa.php: ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Empresa</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .required:after {
            content: " *";
            color: red;
        }
        input[type="text"],
        input[type="file"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
        }
        .checkbox-group {
            margin: 10px 0;
        }
        .checkbox-group label {
            display: inline;
            font-weight: normal;
            margin-left: 5px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .back-btn {
            margin-bottom: 20px;
            display: inline-block;
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
    </style>
</head>
<body>
    <div class="container">
        <a href="menu_principal.php" class="btn back-btn">← Volver al Menú Principal</a>
        
        <h1>Registrar Nueva Empresa</h1>
        
        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($_SESSION['error_message']); ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="ruc" class="required">RUC:</label>
                <input type="text" id="ruc" name="ruc" required pattern="[0-9]{13}" title="13 dígitos numéricos">
                <small>Formato: 13 dígitos numéricos</small>
            </div>
            
            <div class="form-group">
                <label for="razon_social" class="required">Razón Social:</label>
                <input type="text" id="razon_social" name="razon_social" required>
            </div>
            
            <div class="form-group">
                <label for="nombre_comercial" class="required">Nombre Comercial:</label>
                <input type="text" id="nombre_comercial" name="nombre_comercial" required>
            </div>
            
            <div class="form-group">
                <label for="direccion_matriz" class="required">Dirección Matriz:</label>
                <textarea id="direccion_matriz" name="direccion_matriz" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="direccion_establecimiento" class="required">Dirección Establecimiento:</label>
                <textarea id="direccion_establecimiento" name="direccion_establecimiento" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="codigo_establecimiento" class="required">Código Establecimiento:</label>
                <input type="text" id="codigo_establecimiento" name="codigo_establecimiento" required pattern="[0-9]{3}" title="3 dígitos numéricos">
                <small>Formato: 3 dígitos numéricos</small>
            </div>
            
            <div class="form-group">
                <label for="codigo_punto_emision" class="required">Código Punto de Emisión:</label>
                <input type="text" id="codigo_punto_emision" name="codigo_punto_emision" required pattern="[0-9]{3}" title="3 dígitos numéricos">
                <small>Formato: 3 dígitos numéricos</small>
            </div>
            
            <div class="form-group">
                <label for="contribuyente_especial">Contribuyente Especial (Nro. Resolución):</label>
                <input type="text" id="contribuyente_especial" name="contribuyente_especial">
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="obligado_contabilidad" name="obligado_contabilidad">
                <label for="obligado_contabilidad">Obligado a llevar contabilidad</label>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="exportador_bienes" name="exportador_bienes">
                <label for="exportador_bienes">Exportador de Bienes</label>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="contribuyente_rimpe" name="contribuyente_rimpe">
                <label for="contribuyente_rimpe">Contribuyente RIMPE</label>
            </div>
            
            <div class="form-group">
                <label for="agente_de_retencion">Agente de Retención (Nro. Resolución):</label>
                <input type="text" id="agente_de_retencion" name="agente_de_retencion">
            </div>
            
            <div class="form-group">
                <label for="logo">Logo (Imagen):</label>
                <input type="file" id="logo" name="logo" accept="image/jpeg, image/png, image/gif">
                <small>Formatos aceptados: JPEG, PNG, GIF</small>
            </div>
            
            <div class="form-group">
                <label for="tipo_ambiente" class="required">Tipo de Ambiente:</label>
                <select id="tipo_ambiente" name="tipo_ambiente" required>
                    <option value="PRUEBAS">Pruebas</option>
                    <option value="PRODUCCION">Producción</option>
                </select>
            </div>
            
            <button type="submit" class="btn">Guardar Empresa</button>
        </form>
    </div>
</body>
</html>