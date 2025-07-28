<?php
session_start();
include("conexion.php");

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";

    if (empty($username) || empty($password)) {
        $mensaje = "<p style='color: red;'>❌ Todos los campos son obligatorios.</p>";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, role_id FROM usuarios WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();

            if (password_verify($password, $usuario["password"])) {
                $_SESSION["usuario_id"] = $usuario["id"];
                $_SESSION["username"] = $usuario["username"];
                $_SESSION["role_id"] = $usuario["role_id"];

                // Redirigir a dashboard o página principal después del login exitoso
                header("Location: index.php"); // Cambialo por la página que tengas
                exit();
            } else {
                $mensaje = "<p style='color: red;'>❌ Contraseña incorrecta.</p>";
            }
        } else {
            $mensaje = "<p style='color: red;'>❌ El usuario no existe.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - UBPSHARED</title>
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
    <main class="login-container">
        <h1>Iniciar Sesión</h1>

        <!-- Mensaje de error o éxito -->
        <?= $mensaje ?>

        <form class="login-form" method="POST" action="login.php">
            <label for="username">Usuario:</label>
            <input type="text" id="username" name="username" placeholder="Ingrese su usuario" required />

            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" placeholder="Ingrese su contraseña" required />

            <button type="submit" class="login-button">Login</button>
        </form>

        <p class="register-link">
            ¿No tienes cuenta? <a href="register.php">Regístrate aquí</a>
        </p>
    </main>
    <footer>
        <span>© 2025 UBPSHARED. Todos los derechos reservados.</span>
    </footer>
</body>
</html>
