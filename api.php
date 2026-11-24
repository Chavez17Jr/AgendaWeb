<?php
/* api.php · P10 · eventos propios en JSON */
session_start();

header('Content-Type: application/json; charset=utf-8');

function responder(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

// Esta API mantiene la sesión utilizada por acceso.php, pero nunca redirige a HTML.
if (empty($_SESSION['usuario_id'])) {
    responder(401, ['error' => 'Inicia sesión para consultar tus eventos.']);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    responder(405, ['error' => 'Método no permitido. Usa GET.']);
}

$usuarioId = (int) $_SESSION['usuario_id'];
$qEntrada = $_GET['q'] ?? '';
$q = mb_substr(trim(is_string($qEntrada) ? $qEntrada : ''), 0, 60, 'UTF-8');
$categoriaEntrada = $_GET['categoria'] ?? 0;
$categoriaId = is_scalar($categoriaEntrada) && is_numeric($categoriaEntrada)
    ? (int) $categoriaEntrada
    : 0;

try {
    require __DIR__ . '/conexion.php';

    $condiciones = ['e.usuario_id = ?'];
    $tipos = 'i';
    $valores = [$usuarioId];

    if ($q !== '') {
        // Los comodines introducidos por la persona se buscan literalmente.
        $patron = '%' . addcslashes($q, '%_\\') . '%';
        $condiciones[] = '(e.titulo LIKE ? OR e.descripcion LIKE ?)';
        $tipos .= 'ss';
        $valores[] = $patron;
        $valores[] = $patron;
    }

    if ($categoriaId > 0) {
        $condiciones[] = 'e.categoria_id = ?';
        $tipos .= 'i';
        $valores[] = $categoriaId;
    }

    $sql = "SELECT e.id, e.titulo, e.fecha,
                   TIME_FORMAT(e.hora, '%H:%i') AS hora,
                   c.nombre AS categoria, e.descripcion
              FROM eventos e
              JOIN categorias c ON c.id = e.categoria_id
             WHERE " . implode(' AND ', $condiciones) . "
             ORDER BY e.fecha, e.hora";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($tipos, ...$valores);
    $stmt->execute();
    $eventos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $mysqli->close();

    responder(200, [
        'usuario' => $_SESSION['usuario_nombre'] ?? '',
        'total' => count($eventos),
        'filtros' => ['q' => $q, 'categoria' => $categoriaId],
        'eventos' => $eventos,
    ]);
} catch (Throwable $e) {
    error_log('AgendaWeb API: ' . $e->getMessage());
    responder(500, ['error' => 'No se pudieron cargar los eventos.']);
}
