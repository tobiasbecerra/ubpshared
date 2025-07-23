<?php
session_start();
include("conexion.php");

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["puntuacion"], $_SESSION['usuario_id']) &&
    isset($_POST["valorar"])
) {
    $puntuacion = intval($_POST["puntuacion"]);
    $usuario_id = $_SESSION['usuario_id'];
    $publicacion_id = intval($_GET['id']);

    if ($puntuacion >= 1 && $puntuacion <= 5) {
        // Evita duplicados: actualiza si ya valoró, inserta si no
        $check = $conn->prepare("SELECT id FROM valoraciones WHERE usuario_id=? AND publicacion_id=?");
        $check->bind_param("ii", $usuario_id, $publicacion_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            // Ya valoró, actualiza
            $update = $conn->prepare("UPDATE valoraciones SET puntuacion=?, fecha=NOW() WHERE usuario_id=? AND publicacion_id=?");
            $update->bind_param("iii", $puntuacion, $usuario_id, $publicacion_id);
            $update->execute();
            $update->close();
        } else {
            // No valoró, inserta
            $insert = $conn->prepare("INSERT INTO valoraciones (usuario_id, publicacion_id, puntuacion, fecha) VALUES (?, ?, ?, NOW())");
            $insert->bind_param("iii", $usuario_id, $publicacion_id, $puntuacion);
            $insert->execute();
            $insert->close();
        }
        $check->close();
        // Redirige para evitar reenvío
        header("Location: detail.php?id=" . $publicacion_id);
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["comentario"], $_POST["valoracion"]) && isset($_SESSION['usuario_id'])) {
    $comentario = trim($_POST["comentario"]);
    $valoracion = intval($_POST["valoracion"]);
    $usuario_id = $_SESSION['usuario_id'];
    $publicacion_id = intval($_GET['id']);

    if ($comentario !== "" && $valoracion >= 1 && $valoracion <= 5) {
        $stmt = $conn->prepare("INSERT INTO comentarios (cuerpo, usuario_id, publicacion_id, fecha_alta, valoracion) VALUES (?, ?, ?, NOW(), ?)");
        $stmt->bind_param("siii", $comentario, $usuario_id, $publicacion_id, $valoracion);
        $stmt->execute();
        $stmt->close();
        // Redirigir para evitar reenvío del formulario
        header("Location: detail.php?id=" . $publicacion_id);
        exit;
    }
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Publicación no encontrada.";
    exit;
}

$id = intval($_GET['id']);

$query = "
    SELECT p.titulo, p.cuerpo, p.archivo, u.username, p.fecha_alta,
           (SELECT COUNT(*) FROM comentarios c WHERE c.publicacion_id = p.id) AS respuestas,
           IFNULL(p.valoracion, 0) AS valoraciones
    FROM publicaciones p
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.id = $id
    LIMIT 1
";

$resultado = $conn->query($query);

if ($resultado && $resultado->num_rows > 0) {
    $fila = $resultado->fetch_assoc();
    ?>
    <!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($fila["titulo"]) ?> - UBPSHARED</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:ital@1&display=swap" rel="stylesheet">
</head>

<body>
    <header>
        <!-- Barra de navegación -->
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
            <div class="brand">
                <a href="index.php" class="brand">UBPSHARED</a>
            </div>
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

        <!-- Barra de búsqueda -->
        <div class="search-box">
            <h2>Detalle de la publicación</h2>
        </div>
    </header>

<?php
    $promedio_valoracion = 0;
$total_valoraciones = 0;
$promedio_query = "
    SELECT AVG(valoracion) AS promedio, COUNT(valoracion) AS total
    FROM comentarios
    WHERE publicacion_id = $id AND valoracion IS NOT NULL
";
$promedio_result = $conn->query($promedio_query);
if ($promedio_result && $row = $promedio_result->fetch_assoc()) {
    $promedio_valoracion = round($row['promedio'], 2);
    $total_valoraciones = $row['total'];
}
?>

    <div class="container">

        <div class="body">
            <div class="authors">
                <p><b>Autor:</b> <?= strtoupper(htmlspecialchars($fila["username"])) ?></p>
                <p><b>Fecha:</b> <?= date("d/m/Y H:i", strtotime($fila["fecha_alta"])) ?></p>
                <i class="fa fa-handshake-o"></i>
            </div>
            <div class="content">
                <h2><?= htmlspecialchars($fila["titulo"]) ?></h2>
                <p><?= nl2br(htmlspecialchars($fila["cuerpo"])) ?></p>
                <?php if (!empty($fila["archivo"])): ?>
                <p><a href="uploads/<?= htmlspecialchars($fila["archivo"]) ?>" download>Descargar archivo adjunto</a></p>
            <?php endif; ?>
            <a href="index.php">Volver al inicio</a>
                <span style="font-size:0.9em;">
                    <br>Valoración promedio: 
                <?= $total_valoraciones > 0 ? $promedio_valoracion . " / 5 (" . $total_valoraciones . " valoraciones)" : "Sin valoraciones" ?>
                </span>
                <div class="comment">
                    <button onclick="showComment()">Comentar</button>
                </div>
            </div>
        </div>

<?php
    if (isset($_SESSION['usuario_id'])): ?>
    <form class="comment-area" method="post" style="margin-bottom:20px;">
        <textarea name="comentario" placeholder="Escribe tu comentario aquí..." required style="width:100%;min-height:60px;resize:vertical;"></textarea>
        <label for="valoracion" style="margin-top:8px;">Valoración:</label>
        <select name="valoracion" id="valoracion" required style="width:60px;">
            <option value="">-</option>
            <option value="1">1 ⭐</option>
            <option value="2">2 ⭐⭐</option>
            <option value="3">3 ⭐⭐⭐</option>
            <option value="4">4 ⭐⭐⭐⭐</option>
            <option value="5">5 ⭐⭐⭐⭐⭐</option>
        </select>
        <input type="submit" value="Enviar" style="margin-top:-10px;">
    </form>
<?php else: ?>
    <div class="comment-area">
        <p style="color:#000;">Debes <a href="login.php" style="color:#c1273b;">iniciar sesión</a> para comentar.</p>
    </div>
<?php endif; ?>


<h2 class="subforum-title">Comentarios</h2>

<!-- Sección de comentarios -->
<div class="comments-container">
<?php
$comentarios_query = "
    SELECT c.cuerpo, c.fecha_alta, u.username, c.valoracion
    FROM comentarios c
    JOIN usuarios u ON c.usuario_id = u.id
    WHERE c.publicacion_id = $id
    ORDER BY c.fecha_alta ASC
";
$comentarios_result = $conn->query($comentarios_query);

if ($comentarios_result && $comentarios_result->num_rows > 0):
    while ($comentario = $comentarios_result->fetch_assoc()):
?>
    <div class="body">
        <div class="authors">
            <div class="username"><a href="#"><?= htmlspecialchars($comentario["username"]) ?></a></div>
            <i class="fa fa-commenting-o"></i>
        </div>
        <div class="content">
    <?= nl2br(htmlspecialchars($comentario["cuerpo"])) ?>
    <br>
    <small><?= date("d/m/Y H:i", strtotime($comentario["fecha_alta"])) ?></small>
    <?php if (isset($comentario["valoracion"])): ?>
        <br><span>Valoración: <?= intval($comentario["valoracion"]) ?> / 5</span>
    <?php endif; ?>
</div>
    </div>
<?php
    endwhile;
else:
    echo '<div class="body"><div class="content">No hay comentarios aún.</div></div>';
endif;
?>
</div>

    <footer>
        <span> © 2025 Universidad Blas Pascal</span>
    </footer>
    <script src="main.js"></script>
</body>
</html>
    <?php
} else {
    echo "Publicación no encontrada.";
}
?>