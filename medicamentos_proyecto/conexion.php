<?php
$servidor = "localhost";
$usuario = "root";
$contrasenia = "";
$base_datos = "medstock";

// Crear la conexión con la base de datos MySQL
$conexion = new mysqli($servidor, $usuario, $contrasenia, $base_datos);

// Verificar si la conexión falló
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
?>
