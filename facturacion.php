<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['error_message'] = 'Debe iniciar sesión primero';
    header('Location: ./php/login.php');
    exit;
}

require_once './php/config.php';

// Inicializar variables importantes
$empresa_seleccionada = null;
$numero_factura = '';
$clientes = [];
$productos = [];

// Obtener empresas del usuario
try {
    $pdo = conectarDB();
    $sql_empresas = "SELECT id, ruc, razon_social, nombre_comercial, 
                    codigo_establecimiento, codigo_punto_emision, ultimo_secuencial
                    FROM empresas 
                    WHERE usuario_id = ?
                    ORDER BY razon_social";
    $stmt_empresas = $pdo->prepare($sql_empresas);
    $stmt_empresas->execute([$_SESSION['usuario_id']]);
    $empresas = $stmt_empresas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error al obtener empresas: " . $e->getMessage();
    header('Location: menu_principal.php');
    exit;
}


// Procesar selección de empresa solo si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['empresa_id'])) {
    $empresa_id = $_POST['empresa_id'];
    
    if ($empresa_id && $empresa_id !== '') {
        // Buscar la empresa seleccionada
        foreach ($empresas as $empresa) {
            if ($empresa['id'] == $empresa_id) {
                $empresa_seleccionada = $empresa;
                break;
            }
        }
        
        if (!$empresa_seleccionada) {
            $_SESSION['error_message'] = 'Empresa no encontrada';
        } else {
            // Generar número de factura solo si se encontró la empresa
            $secuencial = $empresa_seleccionada['ultimo_secuencial'] + 1;
            $numero_factura = sprintf("%03d-%03d-%09d", 
                $empresa_seleccionada['codigo_establecimiento'], 
                $empresa_seleccionada['codigo_punto_emision'], 
                $secuencial);
            
            // Obtener clientes y productos solo si hay empresa seleccionada
            try {
                // Obtener clientes
                $sql_clientes = "SELECT * FROM clientes WHERE empresa_id = ?";
                $stmt_clientes = $pdo->prepare($sql_clientes);
                $stmt_clientes->execute([$empresa_seleccionada['id']]);
                $clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);

                // Obtener productos
                $sql_productos = "SELECT * FROM productos WHERE empresa_id = ?";
                $stmt_productos = $pdo->prepare($sql_productos);
                $stmt_productos->execute([$empresa_seleccionada['id']]);
                $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Error al obtener datos: " . $e->getMessage();
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Facturación</title>
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
        h1, h2 {
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
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
            align-items: center;
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
            margin: 5px;
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
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .total-section {
            text-align: right;
            margin-top: 20px;
            font-size: 1.2em;
            font-weight: bold;
        }
        .empresa-selector {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        .empresa-selector select {
            padding: 10px;
            width: 100%;
            max-width: 500px;
            border-radius: 5px;
            border: 1px solid #ddd;
            background-color: #fff;
            color: #333;
        }
        .empresa-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        #productModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            width: 80%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
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
            .header-info {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sistema de Facturación</h1>
        
        <!-- Selector de empresa -->
        <div class="empresa-selector">
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <label for="empresa_id">Seleccione una empresa:</label>
                <select id="empresa_id" name="empresa_id" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($empresas as $empresa): ?>
                        <option value="<?= $empresa['id'] ?>" 
                            <?= ($empresa_seleccionada && $empresa['id'] == $empresa_seleccionada['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($empresa['nombre_comercial'] ?: $empresa['razon_social']) ?> 
                            (<?= htmlspecialchars($empresa['ruc']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="submit_form" class="btn">Seleccionar</button>
            </form>
        </div>
        
        <?php if (isset($empresa_seleccionada) && $empresa_seleccionada): ?>
            <!-- Mostrar el formulario de facturación solo cuando hay empresa seleccionada -->
            <div class="empresa-info">
                <h2><?= htmlspecialchars($empresa_seleccionada['razon_social']) ?></h2>
                <p>RUC: <?= htmlspecialchars($empresa_seleccionada['ruc']) ?></p>
                <p>N° Factura: <?= $numero_factura ?></p>
            </div>
            
            <!-- Formulario de facturación -->
            <form method="POST" action="facturacion.php" id="facturaForm">
                <input type="hidden" name="empresa_id" value="<?= $empresa_seleccionada['id'] ?>">
                
                <!-- Campos del cliente -->
                <div class="form-group">
                    <label for="cliente_id">Cliente:</label>
                    <select id="cliente_id" name="cliente_id" required>
                        <option value="">Seleccione un cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?= $cliente['id'] ?>">
                                <?= htmlspecialchars($cliente['identificacion'] . ' - ' . $cliente['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <a href="nuevo_cliente.php?empresa_id=<?= $empresa_seleccionada['id'] ?>" class="btn btn-secondary">
                        Nuevo Cliente
                    </a>
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
                            <th>P. Unitario</th>
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
                    <div>Subtotal 0%: <span id="subtotal0">$0.00</span></div>
                    <div>Subtotal 12%: <span id="subtotal12">$0.00</span></div>
                    <div>IVA 12%: <span id="iva">$0.00</span></div>
                    <div>Total: <span id="total">$0.00</span></div>
                </div>

                <div class="form-group">
                    <label for="observaciones">Datos Adicionales:</label>
                    <textarea id="observaciones" name="observaciones" rows="3"></textarea>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn">Generar Factura</button>
                    <a href="menu_principal.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
            <div class="form-group">
    <label for="moneda">Moneda:</label>
    <select id="moneda" name="moneda" required>
        <option value="USD">Dólares americanos (USD)</option>
        <option value="EUR">Euros (EUR)</option>
        <option value="LOCAL">Moneda local</option>
    </select>
</div>

<div class="form-group">
    <label for="guia_remision">Guía de Remisión:</label>
    <input type="text" id="guia_remision" name="guia_remision">
</div>

<div class="form-group">
    <label for="comprador_tipo_identificacion">Tipo Identificación Comprador:</label>
    <select id="comprador_tipo_identificacion" name="comprador_tipo_identificacion" required>
        <option value="04">RUC</option>
        <option value="05">Cédula</option>
        <option value="06">Pasaporte</option>
        <option value="07">Consumidor Final</option>
        <option value="08">Identificación del Exterior</option>
    </select>
</div>

<div class="form-group">
    <label for="comprador_direccion">Dirección Comprador:</label>
    <input type="text" id="comprador_direccion" name="comprador_direccion" required>
</div>

<div class="form-group">
    <label for="propina">Propina (10%):</label>
    <input type="number" id="propina" name="propina" min="0" step="0.01" value="0">
</div>

<!-- Sección de formas de pago -->
<h3>Formas de Pago</h3>
<div id="formasPagoContainer">
    <div class="forma-pago">
        <select name="formas_pago[0][codigo]" required>
            <option value="01">Efectivo</option>
            <option value="02">Cheque</option>
            <option value="03">Transferencia</option>
            <option value="04">Tarjeta de Crédito</option>
        </select>
        <input type="text" name="formas_pago[0][descripcion]" placeholder="Descripción">
        <input type="number" name="formas_pago[0][valor]" min="0" step="0.01" placeholder="Valor" required>
        <input type="number" name="formas_pago[0][plazo]" min="0" placeholder="Plazo (días)">
        <button type="button" class="btn btn-danger" onclick="removeFormaPago(this)">Eliminar</button>
    </div>
</div>
<button type="button" class="btn" onclick="addFormaPago()">Agregar Forma de Pago</button>

<!-- Sección de datos adicionales -->
<h3>Datos Adicionales</h3>
<div id="datosAdicionalesContainer">
    <div class="dato-adicional">
        <input type="text" name="datos_adicionales[0][nombre]" placeholder="Nombre" required>
        <textarea name="datos_adicionales[0][descripcion]" placeholder="Descripción"></textarea>
        <button type="button" class="btn btn-danger" onclick="removeDatoAdicional(this)">Eliminar</button>
    </div>
</div>
<button type="button" class="btn" onclick="addDatoAdicional()">Agregar Dato Adicional</button> 
            <!-- Modal para seleccionar producto -->
            <div id="productModal">
                <div class="modal-content">
                    <h3>Seleccionar Producto</h3>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Precio</th>
                                <th>IVA</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                                <tr>
                                    <td><?= htmlspecialchars($producto['codigo']) ?></td>
                                    <td><?= htmlspecialchars($producto['descripcion']) ?></td>
                                    <td>$<?= number_format($producto['precio'], 2) ?></td>
                                    <td><?= $producto['iva'] == 1 ? '12%' : '0%' ?></td>
                                    <td><button type="button" onclick="selectProduct(<?= htmlspecialchars(json_encode($producto)) ?>" class="btn">Seleccionar</button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="button" onclick="document.getElementById('productModal').style.display = 'none'" class="btn btn-danger" style="margin-top: 15px;">Cancelar</button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
// Variables globales
let productos = <?= json_encode($productos) ?>;
let items = [];
let formaPagoCount = 1;
let datoAdicionalCount = 1;

// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar el formulario
    renderItems();
    
    // Configurar event listeners solo si los elementos existen
    const addProductBtn = document.getElementById('addProductBtn');
    const productModal = document.getElementById('productModal');
    
    if (addProductBtn) {
        addProductBtn.addEventListener('click', function() {
            if (productModal) {
                productModal.style.display = 'flex';
            }
        });
    }
    
    // Configurar botón de cerrar modal si existe
    const closeModalBtn = document.querySelector('#productModal .btn-danger');
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
            if (productModal) {
                productModal.style.display = 'none';
            }
        });
    }
});

// Función para seleccionar un producto
function selectProduct(producto) {
    // Verificar si el producto ya está en la lista
    const existe = items.some(item => item.producto_id === producto.id);
    
    if (!existe) {
        items.push({
            producto_id: producto.id,
            codigo: producto.codigo,
            descripcion: producto.descripcion,
            cantidad: 1,
            precio: producto.precio,
            iva: producto.iva || 12, // Valor por defecto 12%
            ice: producto.ice || 0,
            irbpnr: producto.irbpnr || 0,
            descuento: 0
        });
        renderItems();
    } else {
        alert('Este producto ya fue agregado a la factura');
    }
    
    const productModal = document.getElementById('productModal');
    if (productModal) {
        productModal.style.display = 'none';
    }
}

// Función para renderizar los items en la tabla
function renderItems() {
    const tbody = document.getElementById('itemsBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    let subtotal0 = 0;
    let subtotal12 = 0;
    let iva = 0;
    
    items.forEach((item, index) => {
        const subtotalItem = item.cantidad * item.precio * (1 - item.descuento/100);
        
        if (item.iva == 1) {
            subtotal12 += subtotalItem;
            iva += subtotalItem * 0.12;
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
            <td><button type="button" onclick="removeItem(${index})" class="btn btn-danger">Eliminar</button></td>
            <input type="hidden" name="items[${index}][producto_id]" value="${item.producto_id}">
            <input type="hidden" name="items[${index}][codigo]" value="${item.codigo}">
            <input type="hidden" name="items[${index}][descripcion]" value="${item.descripcion}">
            <input type="hidden" name="items[${index}][iva]" value="${item.iva}">
            <input type="hidden" name="items[${index}][ice]" value="${item.ice}">
            <input type="hidden" name="items[${index}][irbpnr]" value="${item.irbpnr}">
        `;
        tbody.appendChild(row);
    });
    
    // Actualizar totales de manera segura
    const updateElement = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    };
    
    updateElement('subtotal0', `$${subtotal0.toFixed(2)}`);
    updateElement('subtotal12', `$${subtotal12.toFixed(2)}`);
    updateElement('iva', `$${iva.toFixed(2)}`);
    updateElement('total', `$${(subtotal0 + subtotal12 + iva).toFixed(2)}`);
}

// Función para actualizar un item
function updateItem(index, field, value) {
    if (!items[index]) return;
    
    if (field === 'cantidad') {
        items[index][field] = parseInt(value) || 1;
    } else if (field === 'precio' || field === 'descuento') {
        items[index][field] = parseFloat(value) || 0;
    }
    renderItems();
}

// Función para eliminar un item
function removeItem(index) {
    if (confirm('¿Está seguro de eliminar este producto de la factura?')) {
        items.splice(index, 1);
        renderItems();
    }
}

function addFormaPago() {
    const container = document.getElementById('formasPagoContainer');
    if (!container) return;
    
    const newForma = document.createElement('div');
    newForma.className = 'forma-pago';
    newForma.innerHTML = `
        <select name="formas_pago[${formaPagoCount}][codigo]" required>
            <option value="01">Efectivo</option>
            <option value="02">Cheque</option>
            <option value="03">Transferencia</option>
            <option value="04">Tarjeta de Crédito</option>
        </select>
        <input type="text" name="formas_pago[${formaPagoCount}][descripcion]" placeholder="Descripción">
        <input type="number" name="formas_pago[${formaPagoCount}][valor]" min="0" step="0.01" placeholder="Valor" required>
        <input type="number" name="formas_pago[${formaPagoCount}][plazo]" min="0" placeholder="Plazo (días)">
        <button type="button" class="btn btn-danger" onclick="removeFormaPago(this)">Eliminar</button>
    `;
    container.appendChild(newForma);
    formaPagoCount++;
}

function removeFormaPago(button) {
    if (!button || !button.parentElement) return;
    
    if (document.querySelectorAll('.forma-pago').length > 1) {
        button.parentElement.remove();
    } else {
        alert('Debe haber al menos una forma de pago');
    }
}

function addDatoAdicional() {
    const container = document.getElementById('datosAdicionalesContainer');
    if (!container) return;
    
    const newDato = document.createElement('div');
    newDato.className = 'dato-adicional';
    newDato.innerHTML = `
        <input type="text" name="datos_adicionales[${datoAdicionalCount}][nombre]" placeholder="Nombre" required>
        <textarea name="datos_adicionales[${datoAdicionalCount}][descripcion]" placeholder="Descripción"></textarea>
        <button type="button" class="btn btn-danger" onclick="removeDatoAdicional(this)">Eliminar</button>
    `;
    container.appendChild(newDato);
    datoAdicionalCount++;
}

function removeDatoAdicional(button) {
    if (button && button.parentElement) {
        button.parentElement.remove();
    }
}

</script>
</body>
</html>