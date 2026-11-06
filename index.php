<?php
// ════════════════════════════════════════════════════════
// index.php · lista de eventos (y eliminar)
// ════════════════════════════════════════════════════════
require 'conexion.php';
require 'funciones.php';
requerirSesion();

// ---------- Eliminar: solo por POST (un enlace o una visita no pueden borrar nada) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['eliminar_id'] ?? '';
    $id = is_string($id) && ctype_digit($id) ? (int) $id : 0;   // el id solo puede ser un número

    $usuarioId = (int) $_SESSION['usuario_id'];
    $stmt = $mysqli->prepare('DELETE FROM eventos WHERE id = ? AND usuario_id = ?');
    $stmt->bind_param('ii', $id, $usuarioId);
    $stmt->execute();

    // affected_rows = cuántas filas se borraron (0 = ese evento ya no existía)
    $aviso = $stmt->affected_rows > 0 ? 'borrado' : 'noexiste';
    header('Location: index.php?aviso=' . $aviso, true, 303);   // PRG: Post → Redirect → Get
    exit;
}

// ---------- Funciones de la lista ----------

// '2026-10-08' → '08/10/2026'
function formatearFecha(string $fecha): string
{
    return date('d/m/Y', strtotime($fecha));
}

// Recibe UN evento (arreglo asociativo) y DEVUELVE el HTML de su tarjeta
function mostrarEvento(array $ev): string
{
    $id = (int) $ev['id'];
    $cuando = '📅 ' . formatearFecha($ev['fecha']);
    if ($ev['hora']) {
        $cuando .= '  ⏰ ' . substr($ev['hora'], 0, 5);   // '10:30:00' → '10:30'
    }

    $html  = '<article class="event-item">';
    $html .= '<div class="event-details">';
    $html .= '<h3>' . e($ev['titulo']) . ' <span class="event-badge">' . e($ev['categoria']) . '</span></h3>';
    $html .= '<p>' . e($cuando) . '</p>';
    if ($ev['descripcion']) {
        $html .= '<p class="event-description">' . e($ev['descripcion']) . '</p>';
    }
    $html .= '</div>';

    $html .= '<div class="event-actions">';
    $html .= '<a href="editar.php?id=' . $id . '" class="btn-edit">Editar</a>';
    $html .= '<form method="post" action="index.php" onsubmit="return confirm(\'¿Seguro que quieres eliminar este evento?\');">';
    $html .= '<input type="hidden" name="eliminar_id" value="' . $id . '">';
    $html .= '<button type="submit" class="btn-delete">Eliminar</button>';
    $html .= '</form>';
    $html .= '</div>';

    return $html . '</article>';
}

// ---------- Búsqueda por texto y categoría (solo eventos propios) ----------
$usuarioId = (int) $_SESSION['usuario_id'];
$qEntrada = $_GET['q'] ?? '';
$q = mb_substr(trim(is_string($qEntrada) ? $qEntrada : ''), 0, 60);
$categoriaEntrada = $_GET['categoria'] ?? 0;
$categoriaId = is_scalar($categoriaEntrada) && is_numeric($categoriaEntrada)
    ? (int) $categoriaEntrada
    : 0;
$categorias = $mysqli->query('SELECT id, nombre FROM categorias ORDER BY nombre')->fetch_all(MYSQLI_ASSOC);
$idsValidos = array_map('intval', array_column($categorias, 'id'));
if (!in_array($categoriaId, $idsValidos, true)) {
    $categoriaId = 0;
}
$buscando = ($q !== '' || $categoriaId !== 0);

$condiciones = ['e.usuario_id = ?'];
$tipos = 'i';
$valores = [$usuarioId];
if ($q !== '') {
    // LIKE usa % y _ como comodines; se escapan para buscar esos caracteres literalmente.
    $patron = '%' . addcslashes($q, '%_\\') . '%';
    $condiciones[] = '(e.titulo LIKE ? OR e.descripcion LIKE ?)';
    $tipos .= 'ss';
    $valores[] = $patron;
    $valores[] = $patron;
}
if ($categoriaId !== 0) {
    $condiciones[] = 'e.categoria_id = ?';
    $tipos .= 'i';
    $valores[] = $categoriaId;
}

$sql = 'SELECT e.id, e.titulo, e.fecha, e.hora, e.descripcion, c.nombre AS categoria
          FROM eventos e
          JOIN categorias c ON c.id = e.categoria_id
         WHERE ' . implode(' AND ', $condiciones) . '
         ORDER BY e.fecha, e.hora';
$stmt = $mysqli->prepare($sql);
$stmt->bind_param($tipos, ...$valores);
$stmt->execute();
$eventos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---------- Aviso después de guardar, editar o eliminar (?aviso=...) ----------
$avisos = [
    'guardado' => ['texto' => 'Evento guardado correctamente.',   'tipo' => 'ok'],
    'editado'  => ['texto' => 'Evento actualizado correctamente.', 'tipo' => 'ok'],
    'borrado'  => ['texto' => 'Evento eliminado.',                 'tipo' => 'ok'],
    'noexiste' => ['texto' => 'Ese evento ya no existe.',          'tipo' => 'error'],
];
$clave = $_GET['aviso'] ?? '';
$aviso = is_string($clave) ? ($avisos[$clave] ?? null) : null;

$total = count($eventos);

abrirPagina('Mis eventos', 'index.php');
?>
<div class="layout-single">
    <div class="card">
        <h1 class="card__title">Tus eventos guardados</h1>
        <p class="card__meta">
            <?php if ($buscando): ?>
                <?= $total ?> resultado<?= $total === 1 ? '' : 's' ?><?= $q !== '' ? ' para «' . e($q) . '»' : '' ?>
            <?php else: ?>
                <?= $total ?> eventos registrados
            <?php endif; ?>
        </p>

        <form class="buscador" method="get" action="index.php" role="search">
            <input class="form-input" type="search" name="q" value="<?= e($q) ?>" maxlength="60"
                   placeholder="Buscar en título o descripción…" aria-label="Buscar eventos">
            <select class="form-select" name="categoria" aria-label="Filtrar por categoría">
                <option value="0">Todas las categorías</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= $categoriaId === (int) $cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary">Buscar</button>
            <?php if ($buscando): ?>
                <a href="index.php" class="btn-secondary">Limpiar</a>
            <?php endif; ?>
        </form>

        <?php if ($aviso): ?>
            <div class="alert alert--<?= $aviso['tipo'] ?>" role="<?= $aviso['tipo'] === 'error' ? 'alert' : 'status' ?>">
                <?= e($aviso['texto']) ?>
            </div>
        <?php endif; ?>

        <?php if ($total === 0 && $buscando): ?>
            <div class="empty-state">
                <p>Sin resultados para tu búsqueda.</p>
                <a href="index.php" class="btn-secondary">Ver todos mis eventos</a>
            </div>
        <?php elseif ($total === 0): ?>
            <p class="card__meta">No tienes ningún evento guardado. <a href="registrar.php">¡Crea el primero!</a></p>
        <?php else: ?>
            <div class="event-list">
                <?php foreach ($eventos as $ev): ?>
                    <?= mostrarEvento($ev) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
cerrarPagina();
