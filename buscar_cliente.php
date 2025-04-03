<?php
session_start();
require_once './php/config.php';

header('Content-Type: application/json');

try {
    // Validar que la solicitud sea POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener los datos del cuerpo de la solicitud
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validar datos de entrada
    if (empty($input['tipo_identificacion']) || empty($input['identificacion']) || empty($input['empresa_id'])) {
        throw new Exception('Datos de búsqueda incompletos');
    }

    $tipo = $input['tipo_identificacion'];
    $identificacion = $input['identificacion'];
    $empresa_id = $input['empresa_id'];

    // Conectar a la base de datos
    $pdo = conectarDB();

    // Buscar el cliente en la base de datos
    $sql = "SELECT id, nombre, direccion, telefono, email 
            FROM clientes 
            WHERE empresa_id = :empresa_id 
            AND tipo_identificacion = :tipo 
            AND identificacion = :identificacion";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':empresa_id' => $empresa_id,
        ':tipo' => $tipo,
        ':identificacion' => $identificacion
    ]);

    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
        echo json_encode([
            'encontrado' => true,
            'cliente' => [
                'nombre' => $cliente['nombre'],
                'direccion' => $cliente['direccion'],
                'telefono' => $cliente['telefono'],
                'email' => $cliente['email']
            ]
        ]);
    } else {
        echo json_encode([
            'encontrado' => false,
            'mensaje' => 'Cliente no encontrado'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'mensaje' => $e->getMessage()
    ]);
}
?>