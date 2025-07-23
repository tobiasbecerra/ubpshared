<?php
$host = "localhost";
$usuario = "root";
$contrasena = "";
$basedatos = "basemain";

$conn = new mysqli($host, $usuario, $contrasena, $basedatos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>