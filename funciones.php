<?php
// ════════════════════════════════════════════════════════
// funciones.php · lo que comparten todas las páginas
// ════════════════════════════════════════════════════════

// Si una consulta falla, se muestra un mensaje sencillo en lugar de un error de PHP
set_exception_handler(function (Throwable $e): void {
    error_log('AgendaWeb: ' . $e->getMessage());
    http_response_code(500);
    echo 'Ocurrió un error con la base de datos. Intenta de nuevo.';
});

// Escapa un texto antes de ponerlo en el HTML (así nunca se ejecuta como código)
function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Cuántas letras tiene un texto (strlen cuenta bytes: "á" valdría 2)
function largo(string $texto): int
{
    return preg_match_all('/./us', $texto);
}

// Ruta de un CSS o JS con su fecha de cambio (?v=...), para que el navegador
// no siga usando una copia vieja después de actualizar el sitio
function recurso(string $ruta): string
{
    return $ruta . '?v=' . filemtime(__DIR__ . '/' . $ruta);
}

// ---------- Datos ----------

// Las categorías salen de la tabla "categorias": [id => nombre].
// Si agregas una categoría en la base, aparece sola en el formulario.
function obtenerCategorias(mysqli $mysqli): array
{
    $categorias = [];
    $resultado = $mysqli->query('SELECT id, nombre FROM categorias ORDER BY id');
    foreach ($resultado->fetch_all(MYSQLI_ASSOC) as $fila) {
        $categorias[(int) $fila['id']] = $fila['nombre'];
    }
    return $categorias;
}

// Un evento por su id, o null si no existe
function obtenerEvento(mysqli $mysqli, int $id): ?array
{
    $stmt = $mysqli->prepare('SELECT id, titulo, fecha, hora, categoria_id, descripcion FROM eventos WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $evento = $stmt->get_result()->fetch_assoc();
    return $evento ?: null;
}

// Lo que llegó del formulario (POST), sin espacios de sobra
function leerFormulario(): array
{
    $valores = [];
    foreach (['titulo', 'fecha', 'hora', 'categoria_id', 'descripcion'] as $campo) {
        $dato = $_POST[$campo] ?? '';
        $valores[$campo] = is_string($dato) ? trim($dato) : '';
    }
    return $valores;
}

// Validación en el servidor. Devuelve campo => mensaje (si está vacío, todo está bien)
function validarEvento(array $v, array $categorias): array
{
    $errores = [];

    // Los textos deben llegar en UTF-8 (un navegador siempre los manda así);
    // si no, MySQL los rechazaría al guardar
    foreach (['titulo', 'descripcion'] as $campo) {
        if (!preg_match('//u', $v[$campo])) {
            $errores[$campo] = 'El texto tiene caracteres no válidos.';
        }
    }

    if (!isset($errores['titulo'])) {
        if ($v['titulo'] === '') {
            $errores['titulo'] = 'El título es obligatorio.';
        } elseif (largo($v['titulo']) > 120) {
            $errores['titulo'] = 'El título admite hasta 120 caracteres.';
        }
    }

    $fecha = DateTime::createFromFormat('!Y-m-d', $v['fecha']);
    if (!$fecha || $fecha->format('Y-m-d') !== $v['fecha']) {
        $errores['fecha'] = 'Elige una fecha válida.';
    }

    if ($v['hora'] !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $v['hora'])) {
        $errores['hora'] = 'La hora no es válida.';
    }

    // Lista blanca: solo se acepta un id que exista en la tabla categorias
    if (!ctype_digit($v['categoria_id']) || !isset($categorias[(int) $v['categoria_id']])) {
        $errores['categoria_id'] = 'Elige una categoría de la lista.';
    }

    if (!isset($errores['descripcion']) && largo($v['descripcion']) > 500) {
        $errores['descripcion'] = 'La descripción admite hasta 500 caracteres.';
    }

    return $errores;
}

// ---------- Plantilla de página ----------

// Inicio de cada página: <head>, barra de navegación y apertura de <main>
function abrirPagina(string $titulo, string $actual): void
{
    $enlaces = [
        'registrar.php'  => '📝 Registrar Evento',
        'calendario.php' => '📅 Calendario',
        'index.php'      => '✨ Ver Eventos',
    ];
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> · AgendaWeb</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(recurso('css/estilos.css')) ?>">
    <script src="<?= e(recurso('js/tema.js')) ?>"></script>
</head>
<body>

<header class="navbar">
    <nav class="nav-links">
        <?php foreach ($enlaces as $href => $texto): ?>
            <?php if ($href === $actual): ?>
                <a href="<?= e($href) ?>" class="activo" aria-current="page"><?= e($texto) ?></a>
            <?php else: ?>
                <a href="<?= e($href) ?>"><?= e($texto) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <button type="button" id="btn-theme" class="theme-toggle">🌙 Modo Oscuro</button>
</header>

<main class="contenido">
    <?php
}

// Cierre de cada página
function cerrarPagina(): void
{
    ?>
</main>

</body>
</html>
    <?php
}

// ---------- Formulario (lo usan registrar.php y editar.php) ----------

// Atributos para un campo con error: lo marca y lo une con su mensaje
function atributosError(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? ' aria-invalid="true" aria-describedby="error-' . $campo . '"' : '';
}

// Mensaje de error debajo del campo
function mensajeError(array $errores, string $campo): string
{
    return isset($errores[$campo])
        ? '<p class="form-error" id="error-' . $campo . '">' . e($errores[$campo]) . '</p>'
        : '';
}

// El formulario es "pegajoso": si hay errores, vuelve a mostrar lo que la persona escribió.
// $id solo se pasa al editar: viaja en un campo oculto para saber qué evento guardar.
function mostrarFormulario(array $v, array $errores, array $categorias, string $textoBoton, ?int $id = null): void
{
    ?>
    <form method="post" action="<?= $id === null ? 'registrar.php' : 'editar.php' ?>">
        <?php if ($id !== null): ?>
            <input type="hidden" name="id" value="<?= $id ?>">
        <?php endif; ?>

        <div class="form-group">
            <label class="form-label" for="titulo">Título</label>
            <input type="text" class="form-input" name="titulo" id="titulo" required maxlength="120"
                   value="<?= e($v['titulo']) ?>"<?= atributosError($errores, 'titulo') ?>>
            <?= mensajeError($errores, 'titulo') ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="fecha">Fecha</label>
                <input type="date" class="form-input" name="fecha" id="fecha" required
                       value="<?= e($v['fecha']) ?>"<?= atributosError($errores, 'fecha') ?>>
                <?= mensajeError($errores, 'fecha') ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="hora">Hora <span class="form-opcional">(opcional)</span></label>
                <input type="time" class="form-input" name="hora" id="hora"
                       value="<?= e($v['hora']) ?>"<?= atributosError($errores, 'hora') ?>>
                <?= mensajeError($errores, 'hora') ?>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="categoria_id">Categoría</label>
            <select class="form-select" name="categoria_id" id="categoria_id" required<?= atributosError($errores, 'categoria_id') ?>>
                <option value="">— Elige una —</option>
                <?php foreach ($categorias as $idCategoria => $nombre): ?>
                    <option value="<?= $idCategoria ?>"<?= (string) $idCategoria === $v['categoria_id'] ? ' selected' : '' ?>><?= e($nombre) ?></option>
                <?php endforeach; ?>
            </select>
            <?= mensajeError($errores, 'categoria_id') ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="descripcion">Descripción <span class="form-opcional">(opcional)</span></label>
            <textarea class="form-textarea" name="descripcion" id="descripcion" maxlength="500"
                      rows="3"<?= atributosError($errores, 'descripcion') ?>><?= e($v['descripcion']) ?></textarea>
            <?= mensajeError($errores, 'descripcion') ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary"><?= e($textoBoton) ?></button>
            <?php if ($id !== null): ?>
                <a href="index.php" class="link-cancelar">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
    <?php
}
