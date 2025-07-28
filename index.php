<?php
include("conexion.php");

$search_query = "";
$resultado = false;

if (isset($_GET['search_submit']) && !empty($_GET['q'])) {
    $search_query = $_GET['q'];
    $search_param = '%' . $conn->real_escape_string($search_query) . '%';

    // Consulta que busca en título, cuerpo y tema simultáneamente
    $query = "
        SELECT p.id, p.titulo, p.cuerpo, p.archivo, u.username, p.fecha_alta,
               (SELECT COUNT(*) FROM comentarios c WHERE c.publicacion_id = p.id) AS respuestas,
               IFNULL(p.valoracion, 0) AS valoraciones,
               t.titulo AS tema_titulo
        FROM publicaciones p
        JOIN usuarios u ON p.usuario_id = u.id
        JOIN temas t ON p.tema_id = t.id
        WHERE p.titulo LIKE ? OR p.cuerpo LIKE ? OR t.titulo LIKE ?
        ORDER BY p.fecha_alta DESC
    ";

    $stmt = $conn->prepare($query);
    if ($stmt) {
        // Bind 3 veces el mismo parámetro para los 3 LIKE
        $stmt->bind_param("sss", $search_param, $search_param, $search_param);
        $stmt->execute();
        $resultado = $stmt->get_result();
    } else {
        echo "<p style='color: red; padding: 20px;'>Error al preparar la consulta: " . $conn->error . "</p>";
        $resultado = false;
    }
} else {
    // Sin búsqueda, mostrar todas las publicaciones
    $query = "
        SELECT p.id, p.titulo, p.cuerpo, p.archivo, u.username, p.fecha_alta,
               (SELECT COUNT(*) FROM comentarios c WHERE c.publicacion_id = p.id) AS respuestas,
               IFNULL(p.valoracion, 0) AS valoraciones,
               t.titulo AS tema_titulo
        FROM publicaciones p
        JOIN usuarios u ON p.usuario_id = u.id
        JOIN temas t ON p.tema_id = t.id
        ORDER BY p.fecha_alta DESC
    ";
    $resultado = $conn->query($query);
}

$pageTitle = "Foro - UBPSHARED";
include('header.php');
?>

<div class="search-box">
    <form action="index.php" method="GET">
        <div>
            <!-- Eliminamos el select porque no se usa más -->
            <input type="text" name="q" placeholder="Buscar en títulos, contenido y temas..." value="<?= htmlspecialchars($search_query) ?>" />
            <button type="submit" name="search_submit"><i class="fa fa-search"></i></button>
        </div>
    </form>
</div>

<div class="container">
    <div class="subforum">
        <div class="subforum-title">
            <h1>
                <?php
                if (!empty($search_query)) {
                    $cantidad_resultados = ($resultado && is_object($resultado)) ? $resultado->num_rows : 0;
                    echo "Resultados de la búsqueda para: '" . htmlspecialchars($search_query) . "' - <strong>" . $cantidad_resultados . "</strong> resultado" . ($cantidad_resultados == 1 ? "" : "s");
                } else {
                    echo "Últimas publicaciones";
                }
                ?>
            </h1>
        </div>

        <?php
        if ($resultado && $resultado->num_rows > 0) {
            while ($fila = $resultado->fetch_assoc()):
        ?>
        <div class="subforum-row">
            <div class="subforum-icon subforum-column center">
                <i class="fa fa-handshake-o"></i>
            </div>
            <div class="subforum-description subforum-column">
                <h4><a href="detail.php?id=<?= htmlspecialchars($fila['id']) ?>"><?= htmlspecialchars($fila["titulo"]) ?></a></h4>
                <p><?= nl2br(htmlspecialchars($fila["cuerpo"])) ?></p>
                <p><strong>Tema:</strong> <?= htmlspecialchars($fila["tema_titulo"]) ?></p>
            </div>
            <div class="subforum-stats subforum-column center">
                <?php
                $archivos = json_decode($fila["archivo"], true);
                $cantidad_archivos = is_array($archivos) ? count($archivos) : (!empty($fila["archivo"]) ? 1 : 0);
                ?>
                <span>
                    <i class="fa fa-file"></i> <?= $cantidad_archivos ?> archivo<?= $cantidad_archivos == 1 ? '' : 's' ?>
                </span>
            </div>
            <div class="subforum-stats subforum-column center">
                <?php
                $promedio_query = "
                    SELECT AVG(valoracion) AS promedio
                    FROM comentarios
                    WHERE publicacion_id = " . intval($fila["id"]) . " AND valoracion IS NOT NULL
                ";
                $promedio_result = $conn->query($promedio_query);
                $promedio = 0;
                if ($promedio_result && $row = $promedio_result->fetch_assoc()) {
                    $promedio = round($row['promedio'], 1);
                }

                $estrellas = '';
                if ($promedio > 0) {
                    $entero = floor($promedio);
                    $decimal = $promedio - $entero;

                    for ($i = 0; $i < $entero; $i++) {
                        $estrellas .= '⭐';
                    }

                    if ($decimal >= 0.25 && $decimal < 0.75 && $entero < 5) {
                        $estrellas .= '½';
                    } elseif ($decimal >= 0.75 && $entero < 5) {
                        $estrellas .= '⭐';
                    }
                }
                ?>
                <span>
                    <?= $fila["respuestas"] ?>&nbsp;<i class="fa fa-comment-o"></i>
                    <?= $promedio > 0 ? " | $estrellas ($promedio)" : "" ?>
                </span>
            </div>
            <div class="subforum-info subforum-column">
                <b>Autor: <?= strtoupper(htmlspecialchars($fila["username"])) ?></b>
                <br /><small>el <?= date("d/m/Y H:i", strtotime($fila["fecha_alta"])) ?></small>
            </div>
        </div>
        <?php endwhile;
        } else {
            echo "<p style='padding: 20px;'>";
            if (!empty($search_query)) {
                echo "No se encontraron resultados para su búsqueda: '" . htmlspecialchars($search_query) . "'.";
            } else {
                echo "No hay publicaciones para mostrar.";
            }
            echo "</p>";
        }

        if (isset($stmt) && $stmt) {
            $stmt->close();
        }
        ?>
    </div>
</div>

<?php
if (isset($conn)) {
    $conn->close();
}

include("footer.php");
?>
