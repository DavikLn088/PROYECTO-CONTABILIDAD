<?php
    // Inicia la sesión
    session_start();

    // Verifica si el usuario ha iniciado sesión
    if(!isset($_SESSION['usuario'])){
        echo '<script>
                alert("Por favor debes iniciar sesión");
                window.location = "index.php";
              </script>';
        session_destroy();
        die();
    }

    // Inicializa las variables de error y datos del formulario
    $mensajeError = "";
    $nombre = "";
    $apellido = "";
    
    // Verifica si el formulario ha sido enviado
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Obtiene los datos del formulario y los asigna a variables
        $nombre = $_POST['nombre'] ?? '';
        $apellido = $_POST['apellido'] ?? '';
        $sueldo = $_POST['sueldo'] ?? '';
        $bonificacion = $_POST['bonificacion'] ?? '';
        $horasExtras = $_POST['horas_extras'] ?? '';
        $prestamoIESS = $_POST['prestamo_iess'] ?? '';
        $impuestoRenta = $_POST['impuesto_renta'] ?? '';
        $comisariato = $_POST['comisariato'] ?? '';

        // Verifica si todos los campos han sido llenados
        if (empty($nombre) || empty($apellido) || empty($sueldo) || empty($bonificacion) || empty($horasExtras) || empty($prestamoIESS) || empty($impuestoRenta) || empty($comisariato)) {
            $mensajeError = "Por favor, llene todos los campos antes de continuar.";
        } else {
            // Calcula el aporte al IESS y los ingresos y egresos totales
            $aporteIESS = $sueldo * 0.0945;
            $totalIngresos = $sueldo + $bonificacion + $horasExtras + ($sueldo * 0.0833333) + ($sueldo / 12) + (($sueldo * 12) / 12);
            $totalEgresos = $aporteIESS + $prestamoIESS + $impuestoRenta + $comisariato;
            $liquidoPagar = $totalIngresos - $totalEgresos;
        }
    }

    // Función para calcular el décimo tercer sueldo
    function calcularDecimoTercerSueldo($sueldo) {
        return $sueldo;
    }

    // Función para calcular el décimo cuarto sueldo
    function calcularDecimoCuartoSueldo($salarioBasico) {
        return $salarioBasico / 12;
    }

    // Función para calcular los fondos de reserva
    function calcularFondosReserva($sueldo) {
        return $sueldo * 0.0833333;
    }

    // Función para calcular el valor de las horas extras
    function calcularHorasExtras($sueldo, $horasExtras) {
        $valorHora = ($sueldo / 240) * 2;
        return $horasExtras * $valorHora;
    }

    // Función para calcular los ingresos totales
    function calcularTotalIngresos($sueldo, $bonificacion, $horasExtras, $salarioBasico) {
        $decimoTercero = calcularDecimoTercerSueldo($sueldo);
        $decimoCuarto = calcularDecimoCuartoSueldo($salarioBasico);
        $fondosReserva = calcularFondosReserva($sueldo);
        $extraHoras = calcularHorasExtras($sueldo, $horasExtras);
        return $sueldo + $bonificacion + $extraHoras + $decimoTercero + $decimoCuarto + $fondosReserva;
    }

    // Función para calcular los egresos totales
    function calcularTotalEgresos($aporteIESS, $prestamoIESS, $impuestoRenta, $comisariato) {
        return $aporteIESS + $prestamoIESS + $impuestoRenta + $comisariato;
    }

     
    // Función para calcular el líquido a pagar
    function calcularLiquidoPagar($totalIngresos, $totalEgresos) {
        return $totalIngresos - $totalEgresos;
    }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $sueldo = $_POST['sueldo'];
        $bonificacion = $_POST['bonificacion'] ?? 0;
        $horasExtras = $_POST['horas_extras'] ?? 0;
        $prestamoIESS = $_POST['prestamo_iess'] ?? 0;
        $impuestoRenta = $_POST['impuesto_renta'] ?? 0;
        $comisariato = $_POST['comisariato'] ?? 0;
        $salarioBasico = 450;
        $aporteIESS = $sueldo * 0.0945;
        
        $totalIngresos = calcularTotalIngresos($sueldo, $bonificacion, $horasExtras, $salarioBasico);
        $totalEgresos = calcularTotalEgresos($aporteIESS, $prestamoIESS, $impuestoRenta, $comisariato);
        $liquidoPagar = calcularLiquidoPagar($totalIngresos, $totalEgresos);
        }
        function generarArchivoTxt($nombre, $apellido, $totalIngresos, $totalEgresos, $liquidoPagar) {
            $contenido = "Empleado: $nombre $apellido\n";
            $contenido .= "Total Ingresos: $$totalIngresos\n";
            $contenido .= "Total Egresos: $$totalEgresos\n";
            $contenido .= "Líquido a Pagar: $$liquidoPagar\n";

        $archivo = "reporte_rol_pagos.txt";
        file_put_contents($archivo, $contenido);
        header('Content-Disposition: attachment; filename="' . $archivo . '"');
        readfile($archivo);
        exit;
    }


// Si se presiona el botón de "descargar", se genera un archivo de texto con los datos del usuario.
if (isset($_POST['descargar'])) {
    generarArchivoTxt($nombre, $apellido, $totalIngresos, $totalEgresos, $liquidoPagar);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rol de Pagos</title>
    <link rel="stylesheet" href="./css/style_principal.css">
    <style>
    /* General */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif; /* Fuente más legible */
}   

    main {
        width: 100%;
        padding: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }

    .contenedor__todo {
        width: 100%;
        max-width: 800px;
        margin: auto;
        position: relative;
    }

    form {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-top: 20px;
    align-items: center;
}

    input, button {
        padding: 10px;
        border-radius: 5px;
        border: none;
        width: 80%;
    }

    button {
        background-color: #007bff;
        color: white;
        cursor: pointer;
    }

    button:hover {
        background-color: #0056b3;
    }

    .contenedor {
    max-width: 600px;
    margin: 50px auto;
    text-align: center;
    color: #fff; /* Letras blancas para contraste */
}
h2 {
    font-size: 24px;
    font-weight: bold;
    color: #f1f1f1;
}

h3 {
    font-size: 20px;
    font-weight: normal;
    margin-bottom: 15px;
}

label {
    font-size: 18px;
    font-weight: bold;
    color: #f1f1f1;
    display: block;
    text-align: left;
    width: 80%;
}
input {
    padding: 12px;
    border-radius: 5px;
    border: 1px solid #ccc;
    width: 80%;
    font-size: 16px;
}

    .cuadro-resultados {
        border: 2px solid #000;
        padding: 20px;
        margin-top: 20px;
        font-size: 20px;
        background-color: #f9f9f9;
    }

    .caja__trasera_rol, .cerrar_sesion {
    width: 100%;
    padding: 10px 10px;
    display: flex;
    justify-content: center;
    align-items: center;
    backdrop-filter: blur(2px);
    background-color: rgba(9, 50, 92, 0.5);
    flex-direction: column;
    }

    .menu_principal,
.cerrar_sesion {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
}
.menu_principal button,
.cerrar_sesion button {
    width: 80%;
    text-align: center;
}

button {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 12px 20px;
    font-size: 16px;
    cursor: pointer;
    border-radius: 8px;
    transition: background 0.3s ease, transform 0.2s ease;
}


button a {
    color: white;
    text-decoration: none;
}

button:hover {
    background-color: #0056b3; /* Azul más oscuro */
    transform: scale(1.05);
}

.error {
    color: red;
}
html, body {
    scroll-behavior: smooth; /* Suaviza el scroll en todos los navegadores */
    margin: 0;
    padding: 0;
}
    </style>
</head>
<body>
<div class="section" id="inicio">Inicio</div>
    <div class="section" id="medio">Sección del Medio</div>
    <div class="section" id="final">Final</div>

    <div class="buttons">
        <button onclick="scrollToTop()">Ir arriba</button>
        <button onclick="scrollToElement('medio')">Ir al Medio</button>
        <button onclick="scrollToBottom()">Ir abajo</button>
    </div>

    <script>
        function scrollToElement(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        }

        function scrollToTop() {
        document.documentElement.scrollTop = 0; // Para navegadores modernos
        document.body.scrollTop = 0; // Para navegadores más antiguos
    }

        function scrollToBottom() {
            window.scrollTo({ top: document.body.scrollHeight, behavior: "smooth" });
        }
    </script>
    <div class="contenedor">
        <h2>Bienvenido, <?php echo $_SESSION['usuario']; ?>!</h2>
        <h3>Ingrese sus datos</h3>
        <?php if ($mensajeError) echo "<p class='error'>$mensajeError</p>"; ?>
        <form method="POST">
            <label>Nombre:</label>
            <input type="text" name="nombre" required value="<?php echo $nombre; ?>">
            <label>Apellido:</label>
            <input type="text" name="apellido" required value="<?php echo $apellido; ?>">
            <label>Sueldo:</label>
            <input type="number" name="sueldo" required>
            <label>Bonificación o Comisión:</label>
            <input type="number" name="bonificacion" required>
            <label>Horas Extras:</label>
            <input type="number" name="horas_extras" required>
            <label>Préstamo IESS:</label>
            <input type="number" name="prestamo_iess" required>
            <label>Impuesto a la Renta:</label>
            <input type="number" name="impuesto_renta" required>
            <label>Comisariato:</label>
            <input type="number" name="comisariato" required>
            <button type="submit">Calcular</button>
            <button type="reset">Refrescar</button>
        </form>
        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$mensajeError): ?>
            <div class="cuadro-resultados">
                <h3>Resultados</h3>
                <p>Empleado: <?php echo "$nombre $apellido"; ?></p>
                <p>Total Ingresos: $<?php echo number_format($totalIngresos, 2); ?></p>
                <p>Total Egresos: $<?php echo number_format($totalEgresos, 2); ?></p>
                <p>Líquido a Pagar: $<?php echo number_format($liquidoPagar, 2); ?></p>
                <form method="POST">
                    <input type="hidden" name="descargar" value="1">
                    <button type="submit">Descargar en TXT</button>
                </form>
                </div>
                    <?php endif; ?>
            </div>
            <div class="menu_principal">
    <button class="btn_menu_principal">
        <a href="menu_principal.php">Volver al Menú Principal</a>
    </button>
</div>

<div class="cerrar_sesion">
    <button class="btn_cerrar_sesion">
        <a href="./php/cerrar_sesion.php">Cerrar sesión</a>
    </button>
</div>
            </div>
        </div>
    </main>
</body>
</html>

