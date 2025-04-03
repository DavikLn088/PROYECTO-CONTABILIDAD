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
$sql_productos = "SELECT id, codigo, descripcion, precio, iva, ice, irbpnr 
                 FROM productos 
                 WHERE empresa_id = ?";
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
    <link rel="stylesheet" href="css/style_factura.css">

    <title>Sistema de Facturación</title>
    
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
                <div class="datos-comprador">
    <h3>Datos del comprador</h3>
    
    <div class="form-group">
        <label for="comprador_tipo">Tipo de Comprador:</label>
        <div class="comprador-tipo-container">
            <select id="comprador_tipo" name="comprador_tipo" class="tipo-comprador" required>
                <option value="07">CONSUMIDOR FINAL</option>
                <option value="04">RUC</option>
                <option value="05">CÉDULA</option>
                <option value="06">PASAPORTE</option>
                <option value="08">IDENTIFICACIÓN DEL EXTERIOR</option>
            </select>
            <div class="comprador-warning">
                <small>Recuerde que a partir de $200 no se puede emitir una factura como Consumidor Final</small>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label for="comprador_identificacion">Identificación:</label>
        <div class="busqueda-container">
            <input type="text" id="comprador_identificacion" name="comprador_identificacion" placeholder="Buscar...">
            <button type="button" class="btn btn-small" onclick="buscarCliente()">
                <i class="fas fa-search"></i> Buscar
            </button>
            <a href="buscar_cliente.php?empresa_id=<?= $empresa_seleccionada['id'] ?? '' ?>" class="btn btn-small btn-secondary">
                <i class="fas fa-plus"></i>
            </a>
        </div>
    </div>

    <div class="form-group">
    <label for="comprador_nombre">RAZÓN SOCIAL/APELLIDOS Y NOMBRES:</label>
    <input type="text" id="comprador_nombre" name="comprador_nombre" readonly>
</div>

<div class="form-group">
    <label for="comprador_direccion">DIRECCIÓN COMPRADOR:</label>
    <input type="text" id="comprador_direccion" name="comprador_direccion" readonly>
</div>

<div class="form-group">
    <label for="comprador_telefono">Teléfono:</label>
    <input type="text" id="comprador_telefono" name="comprador_telefono" readonly>
</div>

<div class="form-group">
    <label for="comprador_email">Email:</label>
    <input type="email" id="comprador_email" name="comprador_email" readonly>
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
let productos = <?= json_encode($productos, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
let items = [];
let formaPagoCount = 1;
let datoAdicionalCount = 1;

// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar el formulario
    renderItems();
    
    // Configurar event listeners solo si los elementos existen
    const addProductBtn = document.getElementById('addProductBtn');
    if (addProductBtn) {
        addProductBtn.addEventListener('click', function() {
            const productModal = document.getElementById('productModal');
            if (productModal) {
                productModal.style.display = 'flex';
            }
        });
    }

    const facturaForm = document.getElementById('facturaForm');
    if (facturaForm) {
        facturaForm.addEventListener('submit', function(e) {
            if (items.length === 0) {
                e.preventDefault();
                alert('Debe agregar al menos un producto a la factura');
                return false;
            }
            
            const clienteSelect = document.getElementById('cliente_id');
            if (!clienteSelect || clienteSelect.value === '') {
                e.preventDefault();
                alert('Debe seleccionar un cliente');
                return false;
            }
        });
    }
    
    // Configurar botón de cerrar modal si existe
    const closeModalBtn = document.querySelector('#productModal .btn-danger');
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
            const productModal = document.getElementById('productModal');
            if (productModal) {
                productModal.style.display = 'none';
            }
        });
    }
});

function buscarCliente() {
    const tipo = document.getElementById('comprador_tipo').value;
    const identificacion = document.getElementById('comprador_identificacion').value.trim();
    
    if (!identificacion) {
        alert('Por favor ingrese un número de identificación');
        return;
    }

    // Mostrar indicador de carga
    const buscarBtn = document.querySelector('.busqueda-container .btn');
    buscarBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
    buscarBtn.disabled = true;

    // Configurar los datos a enviar
    const datos = {
        tipo_identificacion: tipo,
        identificacion: identificacion,
        empresa_id: <?= $empresa_seleccionada['id'] ?? 0 ?>
    };

    // Realizar la llamada AJAX
    fetch('buscar_cliente.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(datos)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.error) {
            throw new Error(data.error);
        }
        
        if (data.encontrado) {
            document.getElementById('comprador_nombre').value = data.cliente.nombre;
            document.getElementById('comprador_direccion').value = data.cliente.direccion;
            document.getElementById('comprador_telefono').value = data.cliente.telefono || '';
            document.getElementById('comprador_email').value = data.cliente.email || '';
            document.getElementById('comprador_id').value = data.cliente.id;
            // Puedes agregar más campos si es necesario
        } else {
            
            alert('Cliente no encontrado. ¿Desea crear uno nuevo?');
            ['nombre', 'direccion', 'telefono', 'email'].forEach(campo => {
        document.getElementById(`comprador_${campo}`).value = '';
    });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al buscar cliente: ' + error.message);
    })
    .finally(() => {
        // Restaurar el botón a su estado original
        buscarBtn.innerHTML = '<i class="fas fa-search"></i> Buscar';
        buscarBtn.disabled = false;
    });
}

// Función corregida para seleccionar producto
function selectProduct(producto) {
    try {
        // Verificar si el producto es un string JSON y parsearlo
        if (typeof producto === 'string') {
            try {
                producto = JSON.parse(producto.replace(/&quot;/g, '"'));
            } catch (e) {
                console.error('Error parsing product:', e);
                return;
            }
        }

        // Verificar si el producto ya está en la lista
        const existe = items.some(item => item.producto_id == producto.id);
        
        if (!existe) {
            items.push({
                producto_id: producto.id,
                codigo: producto.codigo,
                descripcion: producto.descripcion,
                cantidad: 1,
                precio: parseFloat(producto.precio),
                iva: producto.iva ? 1 : 0, // Convertir a 1 o 0 para la base de datos
                ice: parseFloat(producto.ice) || 0,
                irbpnr: parseFloat(producto.irbpnr) || 0,
                descuento: 0
            });
            renderItems();
            
            // Cerrar el modal
            const productModal = document.getElementById('productModal');
            if (productModal) {
                productModal.style.display = 'none';
            }
        } else {
            alert('Este producto ya fue agregado a la factura');
        }
    } catch (error) {
        console.error('Error al agregar producto:', error, 'Producto:', producto);
        alert('Ocurrió un error al agregar el producto: ' + error.message);
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