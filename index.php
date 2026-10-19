<?php
// ════════════════════════════════════════════════════════
// index.php · lista de eventos (y eliminar)
// ════════════════════════════════════════════════════════
require 'conexion.php';
require 'funciones.php';

// ---------- Eliminar: solo por POST (un enlace o una visita no pueden borrar nada) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['eliminar_id'] ?? '';
    $id = is_string($id) && ctype_digit($id) ? (int) $id : 0;   // el id solo puede ser un número

    $stmt = $mysqli->prepare('DELETE FROM eventos WHERE id = ?');   // WHERE: borra SOLO ese evento
    $stmt->bind_param('i', $id);
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

// ---------- Los eventos, con el nombre de su categoría (JOIN) ----------
$resultado = $mysqli->query(
    'SELECT e.id, e.titulo, e.fecha, e.hora, e.descripcion, c.nombre AS categoria
       FROM eventos e
       JOIN categorias c ON c.id = e.categoria_id
      ORDER BY e.fecha, e.hora'
);
$eventos = $resultado->fetch_all(MYSQLI_ASSOC);   // un arreglo de arreglos asociativos

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
        <p class="card__meta"><?= $total ?> <?= $total === 1 ? 'evento' : 'eventos' ?></p>

        <?php if ($aviso): ?>
            <div class="alert alert--<?= $aviso['tipo'] ?>" role="<?= $aviso['tipo'] === 'error' ? 'alert' : 'status' ?>">
                <?= e($aviso['texto']) ?>
            </div>
        <?php endif; ?>

        <?php if ($total === 0): ?>
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
