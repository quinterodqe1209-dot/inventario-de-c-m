<?php
// models/ProductoModel.php
// Toda la SQL de productos / categoria / catalogo (antes en views/inventario.php).

require_once __DIR__ . '/../config/conexion.php';

class ProductoModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? (new Conexion())->conn;
    }

    /** Crea columnas extra si faltan (migración idempotente, antes en la vista). */
    public function ensureColumns(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $cols = [
            'PRO_descripcion' => 'TEXT NULL AFTER PRO_nombre_producto',
            'PRO_marca' => 'VARCHAR(100) NULL AFTER PRO_descripcion',
            'PRO_imagen_url' => 'VARCHAR(255) NULL AFTER PRO_marca',
            'PRO_proveedor' => "VARCHAR(150) NOT NULL DEFAULT 'Proveedor por asignar' AFTER PRO_imagen_url",
        ];
        foreach ($cols as $column => $definition) {
            $check = $this->db->prepare(
                "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='productos' AND column_name=?"
            );
            $check->execute([$column]);
            if (!(bool) $check->fetchColumn()) {
                $this->db->exec("ALTER TABLE productos ADD COLUMN `$column` $definition");
            }
        }
    }

    public function search(string $search, ?int $limit = null, int $offset = 0): array
    {
        [$where, $params] = $this->filtroBusqueda($search);
        $sql = 'SELECT PRO_codigo,PRO_nombre_producto,PRO_descripcion,PRO_marca,PRO_imagen_url,PRO_precio_unitario,PRO_stock_actual,PRO_stock_minimo FROM productos WHERE deleted_at IS NULL' . $where . ' ORDER BY PRO_codigo DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contar(string $search): int
    {
        [$where, $params] = $this->filtroBusqueda($search);
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM productos WHERE deleted_at IS NULL' . $where);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** WHERE + params compartidos por search/contar (escapa LIKE). */
    private function filtroBusqueda(string $search): array
    {
        $search = trim(preg_replace('/\s+/', ' ', $search));
        if ($search === '') {
            return ['', []];
        }
        $pattern = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
        $where = ' AND (LOWER(COALESCE(PRO_nombre_producto, "")) LIKE LOWER(?) OR LOWER(COALESCE(PRO_marca, "")) LIKE LOWER(?) OR LOWER(COALESCE(PRO_descripcion, "")) LIKE LOWER(?) OR LOWER(COALESCE(PRO_proveedor, "")) LIKE LOWER(?))';
        return [$where, [$pattern, $pattern, $pattern, $pattern]];
    }

    public function catalog(?string $search = null, ?int $limit = null, int $offset = 0): array
    {
        [$where, $params] = $this->filtroBusqueda((string) ($search ?? ''));
        $sql = 'SELECT PRO_codigo, PRO_nombre_producto, PRO_descripcion, PRO_marca, PRO_imagen_url, PRO_precio_unitario, PRO_stock_actual FROM productos WHERE deleted_at IS NULL' . $where . ' ORDER BY PRO_codigo DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findForOrder(int $codigo): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT PRO_nombre_producto, PRO_stock_actual, PRO_stock_minimo, COALESCE(PRO_stock_maximo, PRO_stock_minimo * 2) AS stock_objetivo, PRO_costo_base, PRO_proveedor FROM productos WHERE PRO_codigo = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$codigo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $d, int $docGerente): int
    {
        $catStmt = $this->db->prepare('SELECT id_categoria FROM categoria WHERE nombre_categoria=? LIMIT 1');
        $catStmt->execute([$d['categoria']]);
        $catId = $catStmt->fetchColumn();
        if (!$catId) {
            $catStmt = $this->db->prepare('INSERT INTO categoria (nombre_categoria,descripcion) VALUES (?,?)');
            $catStmt->execute([$d['categoria'], 'Categoría de productos']);
            $catId = $this->db->lastInsertId();
        }
        $code = (int) $this->db->query('SELECT COALESCE(MAX(PRO_codigo),0)+1 FROM productos')->fetchColumn();
        $stmt = $this->db->prepare(
            'INSERT INTO productos (PRO_codigo,PRO_nombre_producto,PRO_descripcion,PRO_marca,PRO_imagen_url,PRO_precio_unitario,PRO_stock_actual,PRO_stock_minimo,PRO_cantidad_disponible,id_categoria,id_tipo_material,PRO_costo_base) VALUES (?,?,?,?,?,?,?,?,?,?,1,?)'
        );
        $stmt->execute([$code, $d['nombre'], $d['descripcion'], $d['marca'], $d['imagen'], $d['precio'], $d['stock'], $d['minimo'], $d['stock'], $catId, $d['precio']]);
        $cat = $this->db->prepare(
            'INSERT INTO catalogo (CAT_id_producto,GER_documento_identidad,CAT_nombre_producto,CAT_tipo_material,CAT_unidad_empaque,CAT_medidas,CAT_precio_unitario,CAT_foto_empaque) VALUES (?,?,?,?,?,?,?,?)'
        );
        $cat->execute([$code, $docGerente, $d['nombre'], 'Abrasivo', 'Unidad', '', $d['precio'], $d['imagen'] ?: null]);
        return $code;
    }

    public function update(int $code, array $d): void
    {
        $stmt = $this->db->prepare(
            'UPDATE productos SET PRO_nombre_producto=?,PRO_descripcion=?,PRO_marca=?,PRO_imagen_url=?,PRO_precio_unitario=?,PRO_stock_actual=?,PRO_stock_minimo=?,PRO_cantidad_disponible=? WHERE PRO_codigo=?'
        );
        $stmt->execute([$d['nombre'], $d['descripcion'], $d['marca'], $d['imagen'], $d['precio'], $d['stock'], $d['minimo'], $d['stock'], $code]);
        $cat = $this->db->prepare('UPDATE catalogo SET CAT_nombre_producto=?,CAT_precio_unitario=?,CAT_foto_empaque=? WHERE CAT_id_producto=?');
        $cat->execute([$d['nombre'], $d['precio'], $d['imagen'] ?: null, $code]);
    }

    public function softDelete(int $code): bool
    {
        $check = $this->db->prepare('SELECT PRO_codigo FROM productos WHERE PRO_codigo=? AND deleted_at IS NULL');
        $check->execute([$code]);
        if (!$check->fetchColumn()) {
            return false;
        }
        $stmt = $this->db->prepare('UPDATE productos SET deleted_at=NOW() WHERE PRO_codigo=?');
        $stmt->execute([$code]);
        return true;
    }

    public function stats(): array
    {
        $row = $this->db->query(
            'SELECT COUNT(*) AS products, COALESCE(SUM(PRO_stock_actual), 0) AS units, COALESCE(SUM(PRO_stock_actual <= PRO_stock_minimo), 0) AS low FROM productos WHERE deleted_at IS NULL'
        )->fetch(PDO::FETCH_ASSOC);
        return [
            'products' => (int) ($row['products'] ?? 0),
            'stockUnits' => (int) ($row['units'] ?? 0),
            'low' => (int) ($row['low'] ?? 0),
        ];
    }
}
