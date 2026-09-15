<?php
// controllers/InventarioController.php — CRUD productos + subida imagen (antes en views/inventario.php).

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/ProductoModel.php';

class InventarioController extends BaseController
{
    private ProductoModel $model;

    public function __construct()
    {
        $this->model = new ProductoModel($this->db());
    }

    public function canAccess(): bool
    {
        return in_array($_SESSION['rol'] ?? '', ['inventario', 'gerente', 'admin'], true);
    }

    /** Datos para la vista (misma forma que la vista legacy + paginación). */
    public function datos(string $search = '', int $page = 1, int $perPage = 10): array
    {
        $this->model->ensureColumns();
        $page = max(1, $page);
        $total = $this->model->contar($search);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        return [
            'products' => $this->model->search($search, $perPage, ($page - 1) * $perPage),
            'search' => $search,
            'canEdit' => $this->canAccess(),
            'notice' => '',
            'error' => '',
            'paginaActual' => $page,
            'totalPaginas' => $totalPages,
            'totalProductos' => $total,
            'porPagina' => $perPage,
        ];
    }

    /** Procesa create/update/delete. Devuelve [notice, error]. */
    public function guardar(array $post, array $files): array
    {
        $this->model->ensureColumns();
        $action = $post['product_action'] ?? '';
        $code = (int) ($post['codigo'] ?? 0);
        try {
            if (in_array($action, ['create', 'update'], true)) {
                $d = $this->validar($post);
                $d['imagen'] = $this->subirImagen($files['imagen'] ?? [], trim($post['imagen_actual'] ?? ''));
                if ($action === 'create') {
                    $this->model->create($d, (int) ($_SESSION['user']['documento_id'] ?? 0));
                    return ['Producto creado correctamente.', ''];
                }
                $this->model->update($code, $d);
                return ['Producto actualizado correctamente.', ''];
            }
            if ($action === 'delete') {
                if (!$this->model->softDelete($code)) {
                    throw new RuntimeException('Producto no encontrado.');
                }
                return ['Producto eliminado correctamente.', ''];
            }
            return ['', ''];
        } catch (Throwable $e) {
            return ['', $e->getMessage()];
        }
    }

    private function validar(array $post): array
    {
        $d = [
            'nombre' => trim($post['nombre_producto'] ?? ''), 'descripcion' => trim($post['descripcion'] ?? ''),
            'marca' => trim($post['marca'] ?? ''), 'proveedor' => trim($post['proveedor'] ?? 'Proveedor por asignar'),
            'categoria' => trim($post['categoria'] ?? 'Abrasivos'),
            'precio' => filter_var($post['precio'] ?? null, FILTER_VALIDATE_FLOAT),
            'stock' => filter_var($post['stock'] ?? null, FILTER_VALIDATE_INT),
            'minimo' => filter_var($post['stock_minimo'] ?? null, FILTER_VALIDATE_INT),
        ];
        if ($d['nombre'] === '' || $d['precio'] === false || $d['precio'] < 0 || $d['stock'] === false || $d['stock'] < 0 || $d['minimo'] === false || $d['minimo'] < 0) {
            throw new RuntimeException('Completa correctamente nombre, precio y existencias.');
        }
        return $d;
    }

    private function subirImagen(array $file, string $actual): string
    {
        if (empty($file['name'])) {
            return $actual;
        }
        $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (($file['error'] ?? 1) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 3145728 || !isset($tipos[$mime])) {
            throw new RuntimeException('La imagen debe ser JPG, PNG o WEBP y pesar máximo 3 MB.');
        }
        $dir = dirname(__DIR__) . '/assets/img/productos/';
        $web = 'assets/img/productos/';
        $nombre = bin2hex(random_bytes(12)) . '.' . $tipos[$mime];
        if (!move_uploaded_file($file['tmp_name'], $dir . $nombre)) {
            throw new RuntimeException('No fue posible guardar la imagen.');
        }
        return $web . $nombre;
    }
}
