<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($pageTitle)) {
    $pageTitle = 'UBPSHARED';
}

if (!isset($search_query)) {
    $search_query = '';
}
if (!isset($search_type)) {
    $search_type = 'Titulo';
}

if (!isset($conn)) {
    include("conexion.php");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($pageTitle) ?> - UBPSHARED</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
    <link rel="preconnect" href="https://fonts.gstatic.com/" />
    <link rel="icon" href="ubp.png" />
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
                <li class="nav-item"><a href="somos.php">¿Quiénes somos?</a></li>
            </ul>
        </nav>
        <a class="bar-icon" id="iconBar" onclick="hideIconBar()"><i class="fa fa-bars"></i></a>
        <a href="index.php" class="brand">UBPSHARED</a>
        <div class="auth-links">
            <a href="index.php" class="auth-link"><i class="fa fa-home"></i></a> <span class="separator">|</span>
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <span class="auth-link">Hola, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <span class="separator">|</span>
                <a href="logout.php" class="auth-link">Cerrar sesión</a>
            <?php else: ?>
                <a href="login.php" class="auth-link">Iniciar sesión</a>
                <span class="separator">|</span>
                <a href="register.php" class="auth-link">Registrarse</a>
            <?php endif; ?>
        </div>
    </div>
</header>
