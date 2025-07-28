<?php
$host = "localhost";
$usuario = "ubpshared";
$contrasena = "blaschulab2";
$basedatos = "ubpshared_basemain";

$conn = new mysqli($host, $usuario, $contrasena, $basedatos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>