<?php
    session_start();

    if(!isset($_SESSION['usuario'])){
        echo '<script>
                alert("Por favor debes iniciar sesión");
                window.location = "index.php";
              </script>';
        session_destroy();
        die();
    }

    $mensajeError = "";
    $nombre = "";
    $apellido = "";
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nombre = $_POST['nombre'] ?? '';
        $apellido = $_POST['apellido'] ?? '';
        $sueldo = $_POST['sueldo'] ?? '';
        $bonificacion = $_POST['bonificacion'] ?? '';
        $horasExtras = $_POST['horas_extras'] ?? '';
        $prestamoIESS = $_POST['prestamo_iess'] ?? '';
        $impuestoRenta = $_POST['impuesto_renta'] ?? '';
        $comisariato = $_POST['comisariato'] ?? '';

        if (empty($nombre) || empty($apellido) || empty($sueldo) || empty($bonificacion) || empty($horasExtras) || empty($prestamoIESS) || empty($impuestoRenta) || empty($comisariato)) {
            $mensajeError = "Por favor, llene todos los campos antes de continuar.";
        } else {
            $aporteIESS = $sueldo * 0.0945;
            $totalIngresos = $sueldo + $bonificacion + $horasExtras + ($sueldo * 0.0833333) + ($sueldo / 12) + (($sueldo * 12) / 12);
            $totalEgresos = $aporteIESS + $prestamoIESS + $impuestoRenta + $comisariato;
            $liquidoPagar = $totalIngresos - $totalEgresos;
        }
    }


    function calcularDecimoTercerSueldo($sueldo) {
        return $sueldo;
    }

    function calcularDecimoCuartoSueldo($salarioBasico) {
        return $salarioBasico / 12;
    }

    function calcularFondosReserva($sueldo) {
        return $sueldo * 0.0833333;
    }

    function calcularHorasExtras($sueldo, $horasExtras) {
        $valorHora = ($sueldo / 240) * 2;
        return $horasExtras * $valorHora;
    }

    function calcularTotalIngresos($sueldo, $bonificacion, $horasExtras, $salarioBasico) {
        $decimoTercero = calcularDecimoTercerSueldo($sueldo);
        $decimoCuarto = calcularDecimoCuartoSueldo($salarioBasico);
        $fondosReserva = calcularFondosReserva($sueldo);
        $extraHoras = calcularHorasExtras($sueldo, $horasExtras);
        return $sueldo + $bonificacion + $extraHoras + $decimoTercero + $decimoCuarto + $fondosReserva;
    }

    function calcularTotalEgresos($aporteIESS, $prestamoIESS, $impuestoRenta, $comisariato) {
        return $aporteIESS + $prestamoIESS + $impuestoRenta + $comisariato;
    }

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

    if (isset($_POST['descargar'])) {
        generarArchivoTxt($nombre, $apellido, $totalIngresos, $totalEgresos, $liquidoPagar);
    }
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
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        text-decoration: none;
        font-family: sans-serif;
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
        gap: 10px;
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

    .btn_cerrar_sesion {
        margin-top: 5px;
    }
    
    .btn_menu_principal {

        margin-top: 5px;
    }
       
    .error {
        color: red;
    }
    </style>
</head>
<body>
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
                <button id="btn_menu_principal"><a href="menu_principal.php">Volver al Menú Principal</a></button>

            <div class="cerrar_sesion">
                <button id="btn_cerrar_sesion"><a href="./php/cerrar_sesion.php">Cerrar sesión</a></button>
            </div>
        </div>
    </main>
</body>
</html>

