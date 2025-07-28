<?php
include("conexion.php");

$query = "
    SELECT p.titulo, p.cuerpo, u.username, p.fecha_alta,
           (SELECT COUNT(*) FROM comentarios c WHERE c.publicacion_id = p.id) AS respuestas,
           IFNULL(p.valoracion, 0) AS valoraciones
    FROM publicaciones p
    JOIN usuarios u ON p.usuario_id = u.id
    ORDER BY p.fecha_alta DESC
";

$resultado = $conn->query($query);

if ($resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        echo "📌 Título de la Publicación: " . $fila["titulo"] . "\n";
        echo "📝 Contenido de la Publicación:\n" . $fila["cuerpo"] . "\n\n";
        echo "📊 " . $fila["respuestas"] . " Respuestas | " . $fila["valoraciones"] . " Calificaciones\n";
        echo "👤 Publicación realizada por " . strtoupper($fila["username"]) . " el " . $fila["fecha_alta"] . "\n";
        echo str_repeat("─", 50) . "\n";
    }
} else {
    echo "No hay publicaciones para mostrar.\n";
}
?>
