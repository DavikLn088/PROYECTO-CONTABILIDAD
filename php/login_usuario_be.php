<?php

    include 'conexion_be.php';

    $correo = $_POST['correo'];
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];

    $validar_login1 = mysqli_query($conexion, "SELECT * FROM usuarios WHERE correo='$correo' and
    contrasena='$contrasena'");
    if(mysqli_num_rows($validar_login1) > 0){
        header("location:../principal.php");
        exit;
    }else{
        echo '
            <script>
                alert("Correo o Usuario no encontrados, por favor verifique los datos introducidos");
                window.location = "../index.php";
            </script>
        ';
        exit;
    }
    $validar_login2 = mysqli_query($conexion, "SELECT * FROM usuarios WHERE contrasena='$contrasena'");
    if(mysqli_num_rows($validar_login2) > 0){
        header("location:../principal.php");
        exit;
    }else{
        echo '
            <script>
                alert("Contraseña incorrecta, por favor verifique los datos introducidos");
                window.location = "../index.php";
            </script>
        ';
        exit;
    }


?>