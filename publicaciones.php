<?php
session_start();
include("conexion.php");

$mensaje = "";

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
} else {
    $mensaje = "<p style='color: red;'>⚠️ Debes iniciar sesión para crear una publicación.</p>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subí tu publicación - UBPSHARED</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:ital@1&display=swap" rel="stylesheet">
</head>

<body>
    <header>
        <div class="navbar">
            <nav class="navigation hide" id="navigation">
                <span class="close-icon" id="close-icon" onclick="showIconBar()"><i class="fa fa-close"></i></span>
                <ul class="nav-list">
                    <li class="nav-item"><a href="index.php">Inicio</a></li>
                    <li class="nav-item"><a href="publicaciones.php">Subí tu aporte</a></li>
                    <li class="nav-item"><a href="somos.php">¿Quiénes somos?</a></li>
                </ul>
            </nav>
            <a class="bar-icon" id="iconBar" onclick="hideIconBar()"><i class="fa fa-bars"></i></a>
            <div class="brand">UBPSHARED</div>
            <div class="auth-links">
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <span class="auth-link">Hola, <?= htmlspecialchars($_SESSION['username']) ?></span>
                    <a href="logout.php" class="auth-link">Cerrar sesión</a>
                <?php else: ?>
                    <a href="login.php" class="auth-link">Iniciar sesión</a>
                    <a href="register.php" class="auth-link">Registrarse</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="publicacion-form-container">
        <h2>Subí tu publicación</h2>
        <?= $mensaje ?>

        <?php if (isset($_SESSION["usuario_id"])): ?>
        <form method="POST" enctype="multipart/form-data">
            <label for="titulo">Título:</label>
            <input type="text" id="titulo" name="titulo" required>

            <label for="contenido">Contenido / Comentario:</label>
            <textarea id="contenido" name="contenido" rows="5" required></textarea>

            <label for="tema_id">Tema:</label>
            <select id="tema_id" name="tema_id" required>
                <option value="">-- Seleccioná un tema --</option>
                <?php foreach ($temas as $tema): ?>
                    <option value="<?= $tema["id"] ?>"><?= htmlspecialchars($tema["titulo"]) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="archivo">Subir archivo:</label>
            <input type="file" id="archivo" name="archivo">

            <button type="submit">Publicar</button>
        </form>
        <?php endif; ?>
    </div>

    <footer>
        <span>© 2025 Universidad Blas Pascal</span>
    </footer>

    <script src="main.js"></script>
</body>
</html>
