<?php
$servername = "localhost";
$username = "nicole";
$password = "123";
$database = "db_login_registro";

$conexion = new mysqli($servername, $username, $password, $database);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
?>
