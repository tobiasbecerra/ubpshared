<?php
include("conexion.php");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Manejo de valoración (POST)
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["puntuacion"], $_SESSION['usuario_id']) &&
    isset($_POST["valorar"])
) {
    $puntuacion = intval($_POST["puntuacion"]);
    $usuario_id = $_SESSION['usuario_id'];
    $publicacion_id = intval($_GET['id']);

    if ($puntuacion >= 1 && $puntuacion <= 5) {
        $check = $conn->prepare("SELECT id FROM valoraciones WHERE usuario_id=? AND publicacion_id=?");
        $check->bind_param("ii", $usuario_id, $publicacion_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $update = $conn->prepare("UPDATE valoraciones SET puntuacion=?, fecha=NOW() WHERE usuario_id=? AND publicacion_id=?");
            $update->bind_param("iii", $puntuacion, $usuario_id, $publicacion_id);
            $update->execute();
            $update->close();
        } else {
            $insert = $conn->prepare("INSERT INTO valoraciones (usuario_id, publicacion_id, puntuacion, fecha) VALUES (?, ?, ?, NOW())");
            $insert->bind_param("iii", $usuario_id, $publicacion_id, $puntuacion);
            $insert->execute();
            $insert->close();
        }
        $check->close();
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
           IFNULL(p.valoracion, 0) AS valoraciones,
           t.titulo AS tema_titulo
    FROM publicaciones p
    JOIN usuarios u ON p.usuario_id = u.id
    JOIN temas t ON p.tema_id = t.id
    WHERE p.id = $id
    LIMIT 1
";

$resultado = $conn->query($query);

if ($resultado && $resultado->num_rows > 0) {
    $fila = $resultado->fetch_assoc();

    $pageTitle = htmlspecialchars($fila["titulo"]) . " - UBPSHARED";

    include('header.php');
?>

<div class="search-box">
    <h2>Detalle de la publicación:</h2>
</div>

<div class="container">
    <div class="body">
        <div class="authors">
            <p><b>Autor:</b> <?= strtoupper(htmlspecialchars($fila["username"])) ?></p>
            <p><b>Fecha:</b> <?= date("d/m/Y H:i", strtotime($fila["fecha_alta"])) ?></p>
            <p><b>Tema:</b> <?= htmlspecialchars($fila["tema_titulo"]) ?></p>
            <i class="fa fa-handshake-o"></i>
        </div>
        <div class="content">
            <h2><?= htmlspecialchars($fila["titulo"]) ?></h2>
            <p><?= nl2br(htmlspecialchars($fila["cuerpo"])) ?></p>

            <?php if (!empty($fila["archivo"])): ?>
                <div style="margin-top: 10px;">
                    <strong>Archivos adjuntos:</strong>
                    <ul>
                    <?php
                        $archivos = explode(',', $fila["archivo"]);
                        foreach ($archivos as $archivo):
                            $archivo = trim($archivo);
                            if (strtolower(pathinfo($archivo, PATHINFO_EXTENSION)) === "pdf"):
                    ?>
                        <li>
                            <a href="uploads/<?= htmlspecialchars($archivo) ?>" download>
                                <i class="fa fa-file-pdf-o" style="color:red;"></i> <?= htmlspecialchars($archivo) ?>
                            </a>
                        </li>
                    <?php
                            endif;
                        endforeach;
                    ?>
                    </ul>
                </div>
            <?php endif; ?>

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

            $estrellas = '';
            if ($promedio_valoracion > 0) {
                $entero = floor($promedio_valoracion);
                for ($i = 0; $i < $entero; $i++) {
                    $estrellas .= '⭐';
                }
                if ($promedio_valoracion - $entero >= 0.5 && $entero < 5) {
                    $estrellas .= '⭐';
                }
            }
            ?>
            <span style="font-size:0.9em;">
                <br>Valoración promedio:
                <?= $total_valoraciones > 0 ? $estrellas . " (" . $promedio_valoracion . " / 5, " . $total_valoraciones . " valoraciones)" : "Sin valoraciones" ?>
            </span>

            <div class="comment">
                <button onclick="showComment()">Comentar</button>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['usuario_id'])): ?>
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
                    <?php if (isset($comentario["valoracion"])): 
                        $val = intval($comentario["valoracion"]);
                        $estrellas_coment = '';
                        for ($i = 0; $i < $val; $i++) {
                            $estrellas_coment .= '⭐';
                        }
                    ?>
                        <br><span>Valoración: <?= $estrellas_coment ?> (<?= $val ?>/5)</span>
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

</div>

<?php
} else {
    echo "Publicación no encontrada.";
}

if (isset($conn)) {
    $conn->close();
}

include("footer.php");
?>

