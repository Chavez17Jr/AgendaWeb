<?php
require 'conexion.php';
require 'funciones.php';

if (!empty($_SESSION['usuario_id'])) {
    header('Location: index.php', true, 303);
    exit;
}

$modo = ($_POST['modo'] ?? $_GET['modo'] ?? 'login') === 'registro' ? 'registro' : 'login';
$error = '';
$correo = '';
$nombre = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = strtolower(trim(is_string($_POST['correo'] ?? null) ? $_POST['correo'] : ''));
    $nombre = trim(is_string($_POST['nombre'] ?? null) ? $_POST['nombre'] : '');
    $contrasena = is_string($_POST['contrasena'] ?? null) ? $_POST['contrasena'] : '';

    if ($modo === 'registro' && ($nombre === '' || largo($nombre) > 80)) {
        $error = 'Escribe tu nombre (máximo 80 caracteres).';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 120) {
        $error = 'Escribe un correo electrónico válido.';
    } elseif ($modo === 'registro' && strlen($contrasena) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($contrasena === '') {
        $error = 'Escribe tu contraseña.';
    } elseif ($modo === 'registro') {
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        try {
            $stmt = $mysqli->prepare('INSERT INTO usuarios (nombre, correo, clave_hash) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $nombre, $correo, $hash);
            $stmt->execute();
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = (int) $mysqli->insert_id;
            $_SESSION['usuario_nombre'] = $nombre;
            $_SESSION['usuario_correo'] = $correo;
            // Los eventos creados antes de las cuentas pasan al primer usuario que se registra.
            $stmt = $mysqli->prepare('UPDATE eventos SET usuario_id = ? WHERE usuario_id IS NULL');
            $stmt->bind_param('i', $_SESSION['usuario_id']);
            $stmt->execute();
            header('Location: index.php', true, 303);
            exit;
        } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() === 1062) {
                $error = 'Ya existe una cuenta con ese correo.';
            } else {
                throw $e;
            }
        }
    } else {
        $stmt = $mysqli->prepare('SELECT id, nombre, clave_hash FROM usuarios WHERE correo = ?');
        $stmt->bind_param('s', $correo);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();
        if ($usuario && password_verify($contrasena, $usuario['clave_hash'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = (int) $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_correo'] = $correo;
            header('Location: index.php', true, 303);
            exit;
        }
        $error = 'El correo o la contraseña no son correctos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $modo === 'registro' ? 'Crear cuenta' : 'Iniciar sesión' ?> · AgendaWeb</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(recurso('css/estilos.css')) ?>">
    <script src="<?= e(recurso('js/tema.js')) ?>"></script>
</head>
<body>
<header class="navbar auth-navbar"><strong class="auth-brand">AgendaWeb</strong><button type="button" id="btn-theme" class="theme-toggle">🌙 Modo Oscuro</button></header>
<main class="contenido">
    <section class="card auth-card">
        <p class="auth-eyebrow">TU TIEMPO, EN ORDEN</p>
        <h1 class="card__title"><?= $modo === 'registro' ? 'Crea tu cuenta' : 'Bienvenido de nuevo' ?></h1>
        <p class="card__meta"><?= $modo === 'registro' ? 'Regístrate para empezar a organizar tus eventos.' : 'Inicia sesión para entrar a tu agenda.' ?></p>
        <?php if ($error !== ''): ?><div class="alert alert--error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="acceso.php">
            <input type="hidden" name="modo" value="<?= e($modo) ?>">
            <?php if ($modo === 'registro'): ?><div class="form-group"><label class="form-label" for="nombre">Nombre</label><input class="form-input" type="text" id="nombre" name="nombre" required maxlength="80" autocomplete="name" value="<?= e($nombre) ?>"></div><?php endif; ?>
            <div class="form-group"><label class="form-label" for="correo">Correo electrónico</label><input class="form-input" type="email" id="correo" name="correo" required maxlength="120" autocomplete="email" value="<?= e($correo) ?>"></div>
            <div class="form-group"><label class="form-label" for="contrasena">Contraseña</label><input class="form-input" type="password" id="contrasena" name="contrasena" required <?= $modo === 'registro' ? 'minlength="8"' : '' ?> autocomplete="<?= $modo === 'registro' ? 'new-password' : 'current-password' ?>"></div>
            <button type="submit" class="btn-primary auth-submit"><?= $modo === 'registro' ? 'Crear cuenta' : 'Iniciar sesión' ?></button>
        </form>
        <p class="auth-switch"><?php if ($modo === 'registro'): ?>¿Ya tienes cuenta? <a href="acceso.php">Inicia sesión</a><?php else: ?>¿Aún no tienes cuenta? <a href="acceso.php?modo=registro">Regístrate</a><?php endif; ?></p>
    </section>
</main>
</body>
</html>
