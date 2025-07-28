<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include("conexion.php");

$mensaje = "";

// Mostrar mensaje de éxito si fue redirigido
if (isset($_SESSION["mensaje_exito"])) {
    $mensaje = "<p style='color: green;'>" . $_SESSION["mensaje_exito"] . "</p>";
    unset($_SESSION["mensaje_exito"]);
}

// Solo cargamos los temas si el usuario está logueado
$temas = [];

if (isset($_SESSION["usuario_id"])) {
    $resultado = $conn->query("SELECT id, titulo FROM temas");
    while ($fila = $resultado->fetch_assoc()) {
        $temas[] = $fila;
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $titulo = trim($_POST["titulo"] ?? "");
        $cuerpo = trim($_POST["contenido"] ?? "");
        $tema_id = $_POST["tema_id"] ?? "";

        if (empty($titulo) || empty($cuerpo) || empty($tema_id)) {
            $mensaje = "<p style='color: red;'>❌ Todos los campos son obligatorios.</p>";
        } else {
            $nombre_archivo = "";
            if (isset($_FILES["archivo"]) && $_FILES["archivo"]["error"] === UPLOAD_ERR_OK) {
                $nombre_temporal = $_FILES["archivo"]["tmp_name"];
                $nombre_archivo = basename($_FILES["archivo"]["name"]);
                $nombre_archivo = preg_replace("/[^a-zA-Z0-9_\.-]/", "_", $nombre_archivo);
                move_uploaded_file($nombre_temporal, "uploads/" . $nombre_archivo);
            }

            $stmt = $conn->prepare("INSERT INTO publicaciones (titulo, cuerpo, usuario_id, tema_id, archivo, status) VALUES (?, ?, ?, ?, ?, 'activo')");
            $stmt->bind_param("ssiis", $titulo, $cuerpo, $_SESSION["usuario_id"], $tema_id, $nombre_archivo);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $_SESSION["mensaje_exito"] = "✅ Publicación creada correctamente.";
                header("Location: publicaciones.php"); // Cambiá el nombre si tu archivo se llama distinto
                exit;
            } else {
                $mensaje = "<p style='color: red;'>❌ Error al crear la publicación.</p>";
            }

            $stmt->close();
        }
    }
} else {
    $mensaje = "<p style='color: red;'>⚠️ Debes iniciar sesión para crear una publicación.</p>";
}

$pageTitle = "Subí tu publicación - UBPSHARED";
include("header.php");
?>

<div class="publicacion-form-container">
    <h2>Subí tu publicación</h2>
    <?= $mensaje ?>

    <?php if (isset($_SESSION["usuario_id"])): ?>
    <form method="POST" enctype="multipart/form-data">
        <label for="titulo">Título:</label>
        <input type="text" id="titulo" name="titulo" required value="<?= htmlspecialchars($titulo ?? '') ?>">

        <label for="contenido">Contenido / Comentario:</label>
        <textarea id="contenido" name="contenido" rows="5" required><?= htmlspecialchars($cuerpo ?? '') ?></textarea>

        <label for="tema_id">Tema:</label>
        <select id="tema_id" name="tema_id" required>
            <option value="">-- Seleccioná un tema --</option>
            <?php foreach ($temas as $tema): ?>
                <option value="<?= $tema["id"] ?>" <?= (isset($tema_id) && $tema_id == $tema["id"]) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tema["titulo"]) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="archivo">Subir archivo:</label>
        <input type="file" id="archivo" name="archivo">

        <button type="submit">Publicar</button>
    </form>
    <?php endif; ?>
</div>

<?php include("footer.php"); ?>
