<?php
session_start();

if (!isset($_SESSION['correo'])) {
    echo '<script>
            alert("Por favor debes iniciar sesión");
            window.location = "index.php";
          </script>';
    session_destroy();
    die();
}

$nombre = $_POST['nombre'] ?? '';
$apellido = $_POST['apellido'] ?? '';
$cargo = $_POST['cargo'] ?? '';
$sueldo = $_POST['sueldo'] ?? 0;
$bonificacion = $_POST['bonificacion'] ?? 0;
$transporte = $_POST['transporte'] ?? 0;
$alimentacion = $_POST['alimentacion'] ?? 0;
$horasExtras = $_POST['horas_extras'] ?? 0;
$prestamoIESS = $_POST['prestamo_iess'] ?? 0;
$impuestoRenta = $_POST['impuesto_renta'] ?? 0;
$seguroPrivado = $_POST['seguro_privado'] ?? 0;
$comisariato = $_POST['comisariato'] ?? 0;
$salarioBasico = 450;
if ($salarioBasico < 470) {
    echo '<script>alert("El salario básico no puede ser menor a $470.");</script>';
    $salarioBasico = 470;
}
$aporteIESS = calcularAporteIESS($sueldo);

$totalIngresos = calcularTotalIngresos($sueldo, $bonificacion, $transporte, $alimentacion, $horasExtras, $salarioBasico);
$totalEgresos = calcularTotalEgresos($aporteIESS, $prestamoIESS, $impuestoRenta, $seguroPrivado, $comisariato);
$liquidoPagar = calcularLiquidoPagar($totalIngresos, $totalEgresos);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['descargar'])) {
    $nombre = $_POST['nombre'] ?? 'N/A';
    $apellido = $_POST['apellido'] ?? 'N/A';
    $cargo = $_POST['cargo'] ?? 'N/A';
    $sueldo = $_POST['sueldo'] ?? 0;
    $totalIngresos = $_POST['totalIngresos'] ?? 0;
    $totalEgresos = $_POST['totalEgresos'] ?? 0;
    $liquidoPagar = $_POST['liquidoPagar'] ?? 0;

    $archivo = "reporte.txt";
    $contenido = "Nombre: $nombre $apellido\n";
    $contenido .= "Cargo: $cargo\n";
    $contenido .= "Sueldo: $$sueldo\n";
    $contenido .= "Total Ingresos: $$totalIngresos\n";
    $contenido .= "Total Egresos: $$totalEgresos\n";
    $contenido .= "Líquido a Pagar: $$liquidoPagar\n";

    file_put_contents($archivo, $contenido);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . $archivo);
    readfile($archivo);
    exit;
}

function calcularDecimoTercero($sueldo) {
    return $sueldo / 12;
}
function calcularDecimoCuarto($salarioBasico) {
    return $salarioBasico / 12;
}
function calcularFondosReserva($sueldo) {
    return $sueldo * 0.0833333;
}
function calcularHorasExtras($sueldo, $horasExtras) {
    return ($sueldo / 240) * 2 * $horasExtras;
}
function calcularAporteIESS($sueldo) {
    return $sueldo * 0.0945;
}
function calcularTotalIngresos($sueldo, $bonificacion, $transporte, $alimentacion, $horasExtras, $salarioBasico) {
    return $sueldo + $bonificacion + $transporte + $alimentacion + calcularDecimoTercero($sueldo) + calcularDecimoCuarto($salarioBasico) + calcularFondosReserva($sueldo) + calcularHorasExtras($sueldo, $horasExtras);
}
function calcularTotalEgresos($aporteIESS, $prestamoIESS, $impuestoRenta, $seguroPrivado, $comisariato) {
    return $aporteIESS + $prestamoIESS + $impuestoRenta + $seguroPrivado + $comisariato;
}
function calcularLiquidoPagar($totalIngresos, $totalEgresos) {
    return $totalIngresos - $totalEgresos;
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
            font-family: Arial, sans-serif;
        }

        body {
            background: url('fondo.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .contenedor {
            background: rgba(0, 102, 255, 0.7);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            width: 400px;
            color: white;
        }

        h2 {
            margin-bottom: 20px;
        }

        input, button {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: none;
            border-radius: 5px;
        }

        input {
            background: white;
            color: black;
        }

        button {
            background-color: #0056b3;
            color: white;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }

        button:hover {
            background-color: #003d80;
        }

        a {
            text-decoration: none;
            color: white;
            display: block;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="contenedor">
    <h2>Bienvenido, usuario!</h2>
    <form method="POST">
            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="text" name="apellido" placeholder="Apellido" required>
            <input type="text" name="cargo" placeholder="Cargo" required>
            <input type="number" name="sueldo" placeholder="Sueldo" required>
            <input type="number" name="bonificacion" placeholder="Bonificación">
            <input type="number" name="transporte" placeholder="Transporte">
            <input type="number" name="alimentacion" placeholder="Alimentación">
            <input type="number" name="horas_extras" placeholder="Horas Extras">
            <input type="number" name="prestamo_iess" placeholder="Préstamo IESS">
            <input type="number" name="impuesto_renta" placeholder="Impuesto a la Renta">
            <input type="number" name="seguro_privado" placeholder="Seguro Privado">
            <input type="number" name="comisariato" placeholder="Comisariato">
            <button type="submit">Calcular</button>
        </form>
        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
    <div class="cuadro-resultados">
        <p><strong>Nombre:</strong> <?php echo $nombre . " " . $apellido; ?></p>
        <p><strong>Cargo:</strong> <?php echo $cargo; ?></p>
        <p><strong>Sueldo:</strong> <?php echo $sueldo; ?></p>
        <p><strong>Total Ingresos:</strong> <?php echo $totalIngresos; ?></p>
        <p><strong>Total Egresos:</strong> <?php echo $totalEgresos; ?></p>
        <p><strong>Líquido a Pagar:</strong> <?php echo $liquidoPagar; ?></p>
    </div>
    <form method="POST" action="">
    <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>">
    <input type="hidden" name="apellido" value="<?php echo htmlspecialchars($apellido); ?>">
    <input type="hidden" name="cargo" value="<?php echo htmlspecialchars($cargo); ?>">
    <input type="hidden" name="sueldo" value="<?php echo htmlspecialchars($sueldo); ?>">
    <input type="hidden" name="totalIngresos" value="<?php echo htmlspecialchars($totalIngresos); ?>">
    <input type="hidden" name="totalEgresos" value="<?php echo htmlspecialchars($totalEgresos); ?>">
    <input type="hidden" name="liquidoPagar" value="<?php echo htmlspecialchars($liquidoPagar); ?>">
    <input type="hidden" name="descargar" value="1">
    <button type="submit">Descargar en TXT</button>
</form>


    <?php endif; ?>
    <button><a href="menu_principal.php">Volver al Menú Principal</a></button>
        <button><a href="./php/cerrar_sesion.php">Cerrar sesión</a></button>
    </div>
</body>
</html>