<?php
// ════════════════════════════════════════════════════════
// calendario.php · calendario mensual con los eventos en su fecha
// ════════════════════════════════════════════════════════
require 'conexion.php';
require 'funciones.php';

// Los eventos, en el formato que pide FullCalendar (title, start, url)
$filas = $mysqli->query('SELECT id, titulo, fecha, hora FROM eventos')->fetch_all(MYSQLI_ASSOC);
$eventos_calendario = [];
foreach ($filas as $f) {
    $eventos_calendario[] = [
        'title' => $f['titulo'],
        'start' => $f['hora'] ? $f['fecha'] . 'T' . $f['hora'] : $f['fecha'],   // con hora o de todo el día
        'url'   => 'editar.php?id=' . (int) $f['id'],                           // clic en el evento = editarlo
    ];
}

abrirPagina('Calendario', 'calendario.php');
?>
<div class="layout-calendario">
    <section class="card">
        <h1 class="card__title">Tu calendario</h1>
        <p class="card__meta">Haz clic en un evento para editarlo.</p>
        <div id="calendario"></div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/locales/es.global.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var calendario = new FullCalendar.Calendar(document.getElementById('calendario'), {
            initialView: 'dayGridMonth',
            locale: 'es',
            height: 'auto',   // crece con el mes, sin barra de desplazamiento interna
            headerToolbar: { left: 'prev,next', center: 'title', right: 'today' },
            eventColor: 'var(--color-acento)',
            // json_encode convierte el arreglo de PHP en datos de JavaScript sin riesgo de romper el <script>
            events: <?= json_encode($eventos_calendario, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>
        });
        calendario.render();
    });
</script>
<?php
cerrarPagina();
