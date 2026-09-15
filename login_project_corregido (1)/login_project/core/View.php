<?php
// core/View.php
// Helper MVC: renderiza una vista recibiendo $data sin exponer lógica SQL.
// Mantiene compatibilidad: las vistas legacy siguen funcionando si se llaman directo.

function view(string $vistaRelativa, array $data = []): void
{
    // Marca para que las vistas sepan que ya vienen datos del controlador
    // y NO re-ejecuten sus bloques legacy de POST/SQL.
    $data['__MVC_READY'] = true;
    extract($data, EXTR_SKIP);
    $__vista = __DIR__ . '/../' . ltrim($vistaRelativa, '/');
    if (!is_file($__vista)) {
        http_response_code(404);
        die('Vista no encontrada: ' . htmlspecialchars($vistaRelativa));
    }
    require $__vista;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit();
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Paginador Bootstrap reutilizable (preserva filtros vía $params).
 * $params: pares clave=>valor actuales (action, q, desde, hasta, estado...); se añade page.
 */
function pager_html(int $page, int $totalPages, array $params, string $pageParam = 'page', string $fragment = ''): string
{
    if ($totalPages <= 1) {
        return '';
    }
    $page = max(1, min($page, $totalPages));
    $frag = $fragment !== '' ? '#' . $fragment : '';
    $link = static function (int $p, string $label, bool $active = false, bool $disabled = false) use ($params, $pageParam, $frag): string {
        if ($disabled) {
            return '<li class="page-item disabled"><span class="page-link">' . $label . '</span></li>';
        }
        if ($active) {
            return '<li class="page-item active"><span class="page-link">' . $label . '</span></li>';
        }
        $params[$pageParam] = $p;
        return '<li class="page-item"><a class="page-link" href="index.php?' . htmlspecialchars(http_build_query($params), ENT_QUOTES, 'UTF-8') . $frag . '">' . $label . '</a></li>';
    };
    $html = '<nav aria-label="Paginación"><ul class="pagination justify-content-center flex-wrap">';
    $html .= $link($page - 1, '&laquo;', false, $page <= 1);
    $ini = max(1, $page - 2);
    $fin = min($totalPages, $page + 2);
    if ($ini > 1) {
        $html .= $link(1, '1') . '<li class="page-item disabled"><span class="page-link">…</span></li>';
    }
    for ($i = $ini; $i <= $fin; $i++) {
        $html .= $link($i, (string) $i, $i === $page);
    }
    if ($fin < $totalPages) {
        $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>' . $link($totalPages, (string) $totalPages);
    }
    $html .= $link($page + 1, '&raquo;', false, $page >= $totalPages);
    return $html . '</ul></nav>';
}
