<?php
include("conexion.php");
session_start();

$mensaje = "";

// Verificar sesión
if (!isset($_SESSION["usuario_id"])) {
    $mensaje = "<p style='color: red;'>⚠️ Debes iniciar sesión para crear una publicación.</p>";
} else {
    // Cargar temas desde la base
    $temas = [];
    $resultado = $conn->query("SELECT id, titulo FROM temas");
    while ($fila = $resultado->fetch_assoc()) {
        $temas[] = $fila;
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $titulo = trim($_POST["titulo"] ?? "");
        $cuerpo = trim($_POST["cuerpo"] ?? "");
        $tema_id = $_POST["tema_id"] ?? "";

        if (empty($titulo) || empty($cuerpo) || empty($tema_id)) {
            $mensaje = "<p style='color: red;'>❌ Todos los campos son obligatorios.</p>";
        } else {
            // Manejo del archivo
            $nombre_archivo = "";
            if (isset($_FILES["archivo"]) && $_FILES["archivo"]["error"] === UPLOAD_ERR_OK) {
                $nombre_temporal = $_FILES["archivo"]["tmp_name"];
                $nombre_archivo = basename($_FILES["archivo"]["name"]);
                move_uploaded_file($nombre_temporal, "uploads/" . $nombre_archivo);
            }

            $stmt = $conn->prepare("INSERT INTO publicaciones (titulo, cuerpo, usuario_id, tema_id, archivo, status) VALUES (?, ?, ?, ?, ?, 'activo')");
            $stmt->bind_param("ssiis", $titulo, $cuerpo, $_SESSION["usuario_id"], $tema_id, $nombre_archivo);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $mensaje = "<p style='color: green;'>✅ Publicación creada correctamente.</p>";
            } else {
                $mensaje = "<p style='color: red;'>❌ Error al crear la publicación.</p>";
            }

            $stmt->close();
        }
    }
}
?>

<!-- FORMULARIO HTML -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Crear publicación</title>
</head>
<body>
    <?= $mensaje ?>

    <?php if (isset($_SESSION["usuario_id"])): ?>
        <h2>Subí tu publicación</h2>
        <form method="POST" enctype="multipart/form-data">
            <label>Título:</label><br>
            <input type="text" name="titulo"><br><br>

            <label>Contenido / Comentario:</label><br>
            <textarea name="cuerpo" rows="5" cols="40"></textarea><br><br>

            <label>Tema:</label><br>
            <select name="tema_id">
                <option value="">-- Seleccionar tema --</option>
                <?php foreach ($temas as $tema): ?>
                    <option value="<?= $tema["id"] ?>"><?= htmlspecialchars($tema["titulo"]) ?></option>
                <?php endforeach; ?>
            </select><br><br>

            <label>Subir archivo:</label><br>
            <input type="file" name="archivo"><br><br>

            <button type="submit">Publicar</button>
        </form>
    <?php endif; ?>
</body>
</html>
