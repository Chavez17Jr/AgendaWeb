<?php
// ════════════════════════════════════════════════════════
// registrar.php · formulario para crear un evento
// ════════════════════════════════════════════════════════
require 'conexion.php';
require 'funciones.php';

$categorias = obtenerCategorias($mysqli);
$v = ['titulo' => '', 'fecha' => '', 'hora' => '', 'categoria_id' => '', 'descripcion' => ''];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v = leerFormulario();
    $errores = validarEvento($v, $categorias);

    if (empty($errores)) {
        $hora = $v['hora'] === '' ? null : $v['hora'];                      // opcional: vacío = NULL
        $descripcion = $v['descripcion'] === '' ? null : $v['descripcion'];
        $categoria_id = (int) $v['categoria_id'];

        // Consulta preparada: los datos viajan aparte de la orden SQL
        $stmt = $mysqli->prepare(
            'INSERT INTO eventos (titulo, fecha, hora, categoria_id, descripcion) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssis', $v['titulo'], $v['fecha'], $hora, $categoria_id, $descripcion);
        $stmt->execute();

        header('Location: index.php?aviso=guardado', true, 303);   // PRG: Post → Redirect → Get
        exit;
    }
    // Si hay errores, el formulario se vuelve a mostrar con lo que la persona escribió
}

abrirPagina('Nuevo evento', 'registrar.php');
?>
<div class="layout-single">
    <section class="card">
        <h1 class="card__title">Nuevo evento</h1>
        <p class="card__meta">AgendaWeb de Carlos Chavez</p>

        <?php if (!empty($errores)): ?>
            <div class="alert alert--error" role="alert">Revisa los campos marcados.</div>
        <?php endif; ?>

        <?php mostrarFormulario($v, $errores, $categorias, 'Guardar evento'); ?>
    </section>
</div>
<?php
cerrarPagina();
