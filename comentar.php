<?php
// Mostrar errores en pantalla
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo "No has iniciado sesión.";
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Verificar que se envíen los datos esperados
if (!isset($_POST['publicacion_id']) || !isset($_POST['cuerpo'])) {
    http_response_code(400);
    echo "Faltan datos obligatorios.";
    exit;
}

$publicacion_id = intval($_POST['publicacion_id']);
$cuerpo = trim($_POST['cuerpo']);

if (empty($cuerpo)) {
    http_response_code(400);
    echo "El comentario no puede estar vacío.";
    exit;
}

$conexion = conectarBD();

$sql = "INSERT INTO comentarios (cuerpo, usuario_id, publicacion_id) VALUES (?, ?, ?)";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error al preparar la consulta: " . $conexion->error);
}

$stmt->bind_param("sii", $cuerpo, $usuario_id, $publicacion_id);

if ($stmt->execute()) {
    echo "✅ Comentario publicado correctamente.";
} else {
    echo "❌ Error al publicar el comentario: " . $stmt->error;
}

$stmt->close();
$conexion->close();
?>
