<?php
// facturacion.php
session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['error_message'] = 'Debe iniciar sesión primero';
    header('Location: login.php');
    exit;
}

// Verificar selección de empresa
if (!isset($_SESSION['empresa_id'])) {
    $_SESSION['error_message'] = 'Debe seleccionar una empresa primero';
    header('Location: index.php');
    exit;
}

try {
    $pdo = conectarDB();
    
    // Obtener datos completos de la empresa seleccionada
    $stmt = $pdo->prepare("SELECT * FROM empresas WHERE id = ?");
    $stmt->execute([$_SESSION['empresa_id']]);
    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$empresa) {
        throw new Exception("Empresa no encontrada en la base de datos");
    }

    // Procesar el formulario de facturación si se envió
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Aquí iría el código para procesar la factura
    // ...
    
    // Después de procesar, redirigir a un comprobante
    $_SESSION['factura_procesada'] = true;
    header('Location: comprobante.php');
    exit;
}

// Obtener clientes (usando PDO)
$sql_clientes = "SELECT * FROM clientes WHERE empresa_id = ?";
$stmt_clientes = $pdo->prepare($sql_clientes);
$stmt_clientes->execute([$empresa_id]);
$clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);

// Obtener productos (usando PDO)
$sql_productos = "SELECT * FROM productos WHERE empresa_id = ?";
$stmt_productos = $pdo->prepare($sql_productos);
$stmt_productos->execute([$empresa_id]);
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error de base de datos: " . $e->getMessage();
    header('Location: index.php');
    exit;
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Facturación - <?= htmlspecialchars($empresa['nombre_comercial'] ?: $empresa['razon_social']) ?></title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container { 
            max-width: 1000px; 
            margin: 0 auto; 
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-top: 0;
        }
        .header-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .company-logo {
            max-height: 80px;
        }
        .form-group { 
            margin-bottom: 15px;
            display: flex;
            flex-wrap: wrap;
        }
        label { 
            width: 200px;
            font-weight: bold;
            margin-right: 10px;
        }
        input[type="text"], 
        input[type="number"],
        input[type="date"],
        select,
        textarea {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 200px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th, .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f2f2f2;
        }
        .btn {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
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
        .total-section {
            text-align: right;
            margin-top: 20px;
            font-size: 1.2em;
            font-weight: bold;
        }
        @media (max-width: 768px) {
            label {
                width: 100%;
                margin-bottom: 5px;
            }
            input[type="text"], 
            input[type="number"],
            input[type="date"],
            select,
            textarea {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-info">
            <div>
                <h2>Sistema de Facturación</h2>
                <h3><?= htmlspecialchars($empresa['razon_social']) ?></h3>
                <p>Ambiente: <?= htmlspecialchars($empresa['tipo_ambiente']) ?></p>
            </div>
            <?php if (!empty($empresa['logo'])): ?>
                <img src="<?= htmlspecialchars($empresa['logo']) ?>" alt="Logo" class="company-logo">
            <?php endif; ?>
        </div>

        <form action="facturacion.php" method="POST" id="facturaForm">
            <div class="form-group">
                <label for="cliente">Cliente:</label>
                <select id="cliente" name="cliente_id" required>
                    <option value="">Seleccione un cliente</option>
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= $cliente['id'] ?>">
                            <?= htmlspecialchars($cliente['identificacion'] . ' - ' . $cliente['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <a href="nuevo_cliente.php" class="btn btn-secondary" style="margin-left: 10px;">Nuevo Cliente</a>
            </div>

            <div class="form-group">
                <label for="fecha_emision">Fecha de Emisión:</label>
                <input type="date" id="fecha_emision" name="fecha_emision" required value="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
                <label for="tipo_comprobante">Tipo de Comprobante:</label>
                <select id="tipo_comprobante" name="tipo_comprobante" required>
                    <option value="01">Factura</option>
                    <option value="04">Nota de Crédito</option>
                    <option value="05">Nota de Débito</option>
                    <option value="06">Guía de Remisión</option>
                    <option value="07">Comprobante de Retención</option>
                </select>
            </div>

            <h3>Detalle de la Factura</h3>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Descuento</th>
                        <th>Subtotal</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <!-- Filas de productos se añadirán aquí con JavaScript -->
                </tbody>
            </table>

            <button type="button" class="btn" id="addProductBtn">Añadir Producto</button>

            <div class="total-section">
                <div>Subtotal 15%: <span id="subtotal15">$0.00</span></div>
                <div>Subtotal 0%: <span id="subtotal0">$0.00</span></div>
                <div>IVA 15%: <span id="iva">$0.00</span></div>
                <div>Total: <span id="total">$0.00</span></div>
            </div>

            <div class="form-group">
                <label for="observaciones">Observaciones:</label>
                <textarea id="observaciones" name="observaciones" rows="3"></textarea>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn">Generar Factura</button>
                <a href="index.php" class="btn btn-secondary">Volver</a>
            </div>
        </form>
    </div>

    <!-- Modal para seleccionar producto (usando JavaScript puro) -->
    <div id="productModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background-color: white; padding: 20px; border-radius: 5px; width: 80%; max-width: 600px;">
            <h3>Seleccionar Producto</h3>
            <table style="width: 100%; margin-bottom: 15px;">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                        <th>IVA</th>
                        <th>Seleccionar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $producto): ?>
                        <tr>
                            <td><?= htmlspecialchars($producto['codigo']) ?></td>
                            <td><?= htmlspecialchars($producto['descripcion']) ?></td>
                            <td>$<?= number_format($producto['precio'], 2) ?></td>
                            <td><?= $producto['iva'] == 1 ? '15%' : '0%' ?></td>
                            <td><button type="button" onclick="selectProduct(<?= htmlspecialchars(json_encode($producto)) ?>)" class="btn">Seleccionar</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="button" onclick="document.getElementById('productModal').style.display = 'none'" class="btn btn-secondary">Cancelar</button>
        </div>
    </div>

    <script>
        // Variables globales
        let productos = <?= json_encode($productos) ?>;
        let items = [];
        
        // Función para mostrar el modal de productos
        document.getElementById('addProductBtn').addEventListener('click', function() {
            document.getElementById('productModal').style.display = 'flex';
        });
        
        // Función para seleccionar un producto
        function selectProduct(producto) {
            items.push({
                id: producto.id,
                codigo: producto.codigo,
                descripcion: producto.descripcion,
                cantidad: 1,
                precio: producto.precio,
                iva: producto.iva,
                descuento: 0
            });
            renderItems();
            document.getElementById('productModal').style.display = 'none';
        }
        
        // Función para renderizar los items en la tabla
        function renderItems() {
            const tbody = document.getElementById('itemsBody');
            tbody.innerHTML = '';
            
            let subtotal15 = 0;
            let subtotal0 = 0;
            let iva = 0;
            
            items.forEach((item, index) => {
                const subtotalItem = item.cantidad * item.precio * (1 - item.descuento/100);
                
                if (item.iva == 1) {
                    subtotal15 += subtotalItem;
                    iva += subtotalItem * 0.15;
                } else {
                    subtotal0 += subtotalItem;
                }
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.codigo}</td>
                    <td>${item.descripcion}</td>
                    <td><input type="number" name="items[${index}][cantidad]" value="${item.cantidad}" min="1" step="1" onchange="updateItem(${index}, 'cantidad', this.value)"></td>
                    <td><input type="number" name="items[${index}][precio]" value="${item.precio.toFixed(2)}" min="0" step="0.01" onchange="updateItem(${index}, 'precio', this.value)"></td>
                    <td><input type="number" name="items[${index}][descuento]" value="${item.descuento}" min="0" max="100" step="1" onchange="updateItem(${index}, 'descuento', this.value)">%</td>
                    <td>$${subtotalItem.toFixed(2)}</td>
                    <td><button type="button" onclick="removeItem(${index})" class="btn btn-secondary">Eliminar</button></td>
                `;
                tbody.appendChild(row);
            });
            
            // Actualizar totales
            document.getElementById('subtotal15').textContent = `$${subtotal15.toFixed(2)}`;
            document.getElementById('subtotal0').textContent = `$${subtotal0.toFixed(2)}`;
            document.getElementById('iva').textContent = `$${iva.toFixed(2)}`;
            document.getElementById('total').textContent = `$${(subtotal15 + subtotal0 + iva).toFixed(2)}`;
        }
        
        // Función para actualizar un item
        function updateItem(index, field, value) {
            items[index][field] = field === 'cantidad' ? parseInt(value) : parseFloat(value);
            renderItems();
        }
        
        // Función para eliminar un item
        function removeItem(index) {
            items.splice(index, 1);
            renderItems();
        }
        
        // Inicializar el formulario
        document.addEventListener('DOMContentLoaded', function() {
            // Puedes cargar items existentes si estás editando una factura
            renderItems();
        });
    </script>
</body>
</html>