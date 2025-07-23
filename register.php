<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'conexion.php';

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Traemos los datos enviados
    $nombre_completo = trim($_POST["fullname"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $correo = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if (empty($nombre_completo) || empty($username) || empty($correo) || empty($password)) {
        $mensaje = "<span style='color: red;'>❌ Faltan datos obligatorios.</span>";
    } else {
        // Verificar si el nombre de usuario ya existe
        $check = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $mensaje = "<span style='color: red;'>❌ El nombre de usuario ya está en uso.</span>";
        } else {
            // Hashear la contraseña
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insertar usuario
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre_completo, username, correo, password, role_id, fecha_alta) 
                                    VALUES (?, ?, ?, ?, 2, NOW())");
            $stmt->bind_param("ssss", $nombre_completo, $username, $correo, $hashedPassword);

            if ($stmt->execute()) {
                $mensaje = "<span style='color: green;'>✅ Registro exitoso. Ya podés <a href='login.php'>iniciar sesión</a>.</span>";
                // Opcional: limpiar variables para que el formulario quede vacío
                $nombre_completo = $username = $correo = $password = "";
            } else {
                $mensaje = "<span style='color: red;'>❌ Error al registrar usuario.</span>";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Registrarse</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
    <link rel="preconnect" href="https://fonts.gstatic.com" />
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:ital@1&display=swap" rel="stylesheet" />
</head>
<body>
    <header>
        <div class="navbar">
            <a href="index.php" class="brand">UBPSHARED</a>
        </div>
    </header>

    <main class="register-container">
        <h1>Registrarse</h1>

        <?php if (!empty($mensaje)) echo "<p>$mensaje</p>"; ?>

        <form class="register-form" method="POST" action="">
            <label for="fullname">Nombre Completo:</label>
            <input type="text" id="fullname" name="fullname" placeholder="Ingrese su nombre completo" required
                value="<?= htmlspecialchars($nombre_completo ?? '') ?>" />

            <label for="username">Nombre de Usuario:</label>
            <input type="text" id="username" name="username" placeholder="Ingrese su nombre de usuario" required
                value="<?= htmlspecialchars($username ?? '') ?>" />

            <label for="email">Correo Electrónico:</label>
            <input type="email" id="email" name="email" placeholder="Ingrese su correo electrónico" required
                value="<?= htmlspecialchars($correo ?? '') ?>" />

            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" placeholder="Ingrese su contraseña" required />

            <button type="submit" class="register-button">Registrarse</button>
        </form>

        <p class="login-link">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
        </p>
    </main>

    <footer>
        <span> © 2025 UBPSHARED. Todos los derechos reservados. </span>
    </footer>
</body>
</html>
