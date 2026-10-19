<?php
/* ════════════════════════════════════════════════════════
   conexion.ejemplo.php · PLANTILLA (esta sí se sube a GitHub)

   1. Haz una COPIA de este archivo y llámala  conexion.php
   2. En conexion.php cambia TU_CONTRASEÑA por tus datos reales:
      - En tu computadora: usuario 'root' y la contraseña que elegiste
        al instalar MySQL (la misma con la que entras a Workbench)
      - En DomCloud no hace falta: la receta crea conexion.php con sus datos
   3. conexion.php NUNCA se sube a GitHub (ver .gitignore)
   ════════════════════════════════════════════════════════ */

// Desde PHP 8.1, si algo falla, mysqli lanza una "excepción" (un error que podemos atrapar)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    //                  servidor     usuario  contraseña  base de datos
    $mysqli = new mysqli('localhost', 'root', 'TU_CONTRASEÑA', 'agenda2');
    $mysqli->set_charset('utf8mb4');   // acentos y ñ correctos
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    // La causa exacta queda en el registro del servidor; al visitante no se le muestran datos de acceso
    error_log('AgendaWeb: ' . $e->getMessage());
    die('No se pudo conectar a la base de datos.');
}
