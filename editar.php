<?php
// ════════════════════════════════════════════════════════
// editar.php · cambiar un evento que ya existe
// ════════════════════════════════════════════════════════
require 'conexion.php';
require 'funciones.php';

// De dónde sale el id:
//   al ABRIR (GET)   → de la URL:  editar.php?id=5  (el botón "Editar" de la lista)
//   al GUARDAR (POST) → del campo oculto <input type="hidden" name="id"> del formulario
$id = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['id'] ?? '') : ($_GET['id'] ?? '');
$id = is_string($id) && ctype_digit($id) ? (int) $id : 0;   // el id solo puede ser un número

$evento = obtenerEvento($mysqli, $id);
if ($evento === null) {
    header('Location: index.php?aviso=noexiste', true, 303);
    exit;
}

$categorias = obtenerCategorias($mysqli);
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v = leerFormulario();
    $errores = validarEvento($v, $categorias);

    if (empty($errores)) {
        $hora = $v['hora'] === '' ? null : $v['hora'];
        $descripcion = $v['descripcion'] === '' ? null : $v['descripcion'];
        $categoria_id = (int) $v['categoria_id'];

        // WHERE id = ?: cambia SOLO este evento
        $stmt = $mysqli->prepare(
            'UPDATE eventos SET titulo = ?, fecha = ?, hora = ?, categoria_id = ?, descripcion = ? WHERE id = ?'
        );
        $stmt->bind_param('sssisi', $v['titulo'], $v['fecha'], $hora, $categoria_id, $descripcion, $id);
        $stmt->execute();

        header('Location: index.php?aviso=editado', true, 303);   // PRG
        exit;
    }
} else {
    // Al abrir: el formulario empieza con los datos guardados
    $v = [
        'titulo'       => $evento['titulo'],
        'fecha'        => $evento['fecha'],
        'hora'         => $evento['hora'] ? substr($evento['hora'], 0, 5) : '',   // '10:30:00' → '10:30'
        'categoria_id' => (string) $evento['categoria_id'],
        'descripcion'  => $evento['descripcion'] ?? '',
    ];
}

abrirPagina('Editar evento', '');
?>
<div class="layout-single">
    <section class="card">
        <h1 class="card__title">Editar evento</h1>
        <p class="card__meta">Cambia los datos y guarda.</p>

        <?php if (!empty($errores)): ?>
            <div class="alert alert--error" role="alert">Revisa los campos marcados.</div>
        <?php endif; ?>

        <?php mostrarFormulario($v, $errores, $categorias, 'Actualizar evento', $id); ?>
    </section>
</div>
<?php
cerrarPagina();
