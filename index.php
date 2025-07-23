<?php
session_start();
include("conexion.php"); // Asume que 'conexion.php' establece $conn

// Inicializar variables de búsqueda
$search_query = "";
$search_type = "Titulo"; // Valor por defecto para la búsqueda

// Verificar si se ha enviado el formulario de búsqueda
if (isset($_GET['search_submit']) && !empty($_GET['q'])) {
    $search_query = $_GET['q'];
    $search_type = $_GET['search_type']; // Obtener el tipo de búsqueda (Titulo, Contenido, Tema)

    // Escapar el término de búsqueda para usar en la consulta LIKE
    $search_param = '%' . $conn->real_escape_string($search_query) . '%'; // Usar real_escape_string para LIKE con sentencias preparadas

    // Construir la consulta base
    $base_query = "
        SELECT p.id, p.titulo, p.cuerpo, p.archivo, u.username, p.fecha_alta,
               (SELECT COUNT(*) FROM comentarios c WHERE c.publicacion_id = p.id) AS respuestas,
               IFNULL(p.valoracion, 0) AS valoraciones
        FROM publicaciones p
        JOIN usuarios u ON p.usuario_id = u.id
    ";

    // Construir la cláusula WHERE basada en el tipo de búsqueda
    $query = "";
    $stmt = null; // Inicializar $stmt a null

    switch ($search_type) {
        case "Titulo":
            $query = $base_query . " WHERE p.titulo LIKE ? ORDER BY p.fecha_alta DESC";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("s", $search_param);
            }
            break;
        case "Contenido":
            $query = $base_query . " WHERE p.cuerpo LIKE ? ORDER BY p.fecha_alta DESC";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("s", $search_param);
            }
            break;
        case "Tema":
            // Consulta corregida para usar JOIN con la tabla temas.
            $query = "
                SELECT p.id, p.titulo, p.cuerpo, p.archivo, u.username, p.fecha_alta,
                (SELECT COUNT(*) FROM comentarios c WHERE c.publicacion_id = p.id) AS respuestas,
                (SELECT COUNT(*) FROM comentarios c WHERE c.publicacion_id = p.id AND c.valoracion IS NOT NULL) AS valoraciones
                FROM publicaciones p
                JOIN usuarios u ON p.usuario_id = u.id
                JOIN temas t ON p.tema_id = t.id  -- Correcto: JOIN con la tabla temas por tema_id
                WHERE t.titulo LIKE ?             -- Correcto: Buscar en el título del tema
                ORDER BY p.fecha_alta DESC
            ";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("s", $search_param);
            }
            break;
        default:
            // Por defecto, buscar por título si el tipo es inválido o no especificado
            $query = $base_query . " WHERE p.titulo LIKE ? ORDER BY p.fecha_alta DESC";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("s", $search_param);
            }
            $search_type = "Titulo"; // Asegurar que el select refleje el tipo real
            break;
    }

    if ($stmt) {
        $stmt->execute();
        $resultado = $stmt->get_result();
    } else {
        echo "<p style='color: red; padding: 20px;'>Error al preparar la consulta: " . $conn->error . "</p>";
        $resultado = false; // Indicar que hubo un error
    }

} else {
    // Consulta por defecto si no hay búsqueda
    $query = "
        SELECT p.id, p.titulo, p.cuerpo, p.archivo, u.username, p.fecha_alta,
               (SELECT COUNT(*) FROM comentarios c WHERE c.publicacion_id = p.id) AS respuestas,
               IFNULL(p.valoracion, 0) AS valoraciones
        FROM publicaciones p
        JOIN usuarios u ON p.usuario_id = u.id
        ORDER BY p.fecha_alta DESC
    ";
    $resultado = $conn->query($query);
}

// Asegurarse de que $resultado esté definido incluso si la consulta falla
if (!isset($resultado)) {
    $resultado = false; // Si no se preparó o ejecutó, asegurar que sea falso
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Foro - UBPSHARED</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
    <link rel="preconnect" href="https://fonts.gstatic.com" />
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:ital@1&display=swap" rel="stylesheet" />
</head>

<body>
<header>
    <div class="navbar">
        <nav class="navigation hide" id="navigation">
            <span class="close-icon" id="close-icon" onclick="showIconBar()"><i class="fa fa-close"></i></span>
            <ul class="nav-list">
                <li class="nav-item"><a href="index.php">Inicio</a></li>
                <li class="nav-item"><a href="publicaciones.php">Subí tu aporte</a></li>
                <li class="nav-item"><a href="somos.html">¿Quiénes somos?</a></li>
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

    <div class="search-box">
        <form action="index.php" method="GET">
            <div>
                <select name="search_type" id="search_type">
                    <option value="Titulo" <?= ($search_type == 'Titulo' ? 'selected' : '') ?>>Títulos</option>
                    <option value="Contenido" <?= ($search_type == 'Contenido' ? 'selected' : '') ?>>Contenido</option>
                    <option value="Tema" <?= ($search_type == 'Tema' ? 'selected' : '') ?>>Tema</option>
                </select>
                <input type="text" name="q" placeholder="Buscar ..." value="<?= htmlspecialchars($search_query) ?>" />
                <button type="submit" name="search_submit"><i class="fa fa-search"></i></button>
            </div>
        </form>
    </div>
</header>

<div class="container">
    <div class="subforum">
        <div class="subforum-title">
            <h1>
                <?php
                if (!empty($search_query)) {
                    echo "Resultados de la búsqueda para: '" . htmlspecialchars($search_query) . "' en " . htmlspecialchars($search_type);
                } else {
                    echo "Últimas publicaciones";
                }
                ?>
            </h1>
        </div>

        <?php
        if ($resultado && $resultado->num_rows > 0) {
            while ($fila = $resultado->fetch_assoc()): ?>
                <div class="subforum-row">
                    <div class="subforum-icon subforum-column center">
                        <i class="fa fa-handshake-o"></i>
                    </div>
                    <div class="subforum-description subforum-column">
                        <h4><a href="detail.php?id=<?= htmlspecialchars($fila['id']) ?>"><?= htmlspecialchars($fila["titulo"]) ?></a></h4>
                        <p><?= nl2br(htmlspecialchars($fila["cuerpo"])) ?></p>
                    </div>
                    <div class="subforum-stats subforum-column center">
                        <span>
                            <?= !empty($fila["archivo"]) ? "1 archivo adjunto" : "0 archivos adjuntos" ?>
                        </span>
                    </div>
                    <div class="subforum-stats subforum-column center">
                        <span><?= $fila["respuestas"] ?> Respuestas | <?= $fila["valoraciones"] ?> Calificaciones</span>
                    </div>
                    <div class="subforum-info subforum-column">
                        <b>Publicación realizada por <?= strtoupper(htmlspecialchars($fila["username"])) ?></b>
                        <br /><small>el <?= date("d/m/Y H:i", strtotime($fila["fecha_alta"])) ?></small>
                    </div>
                </div>
        <?php
            endwhile;
        } else {
            echo "<p style='padding: 20px;'>";
            if (!empty($search_query)) {
                echo "No se encontraron resultados para su búsqueda: '" . htmlspecialchars($search_query) . "'.";
            } else {
                echo "No hay publicaciones para mostrar.";
            }
            echo "</p>";
        }

        // Cerrar la sentencia preparada si se usó
        if (isset($stmt) && $stmt) {
            $stmt->close();
        }
        // Cerrar la conexión a la base de datos
        $conn->close();
        ?>
    </div>
</div>

<footer>
    <span> © 2025 Universidad Blas Pascal</span>
</footer>

<script src="main.js"></script>
</body>
</html>