<?php
// models/VentaModel.php + ClienteModel (antes en views/reportes.php).

require_once __DIR__ . '/../config/conexion.php';

class ClienteModel
{
    private PDO $db;
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? (new Conexion())->conn;
    }
    public function ensureTables(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $this->db->exec("CREATE TABLE IF NOT EXISTS clientes (id_cliente INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,nombre VARCHAR(100) NOT NULL,apellido VARCHAR(100) NOT NULL,documento VARCHAR(40) NULL UNIQUE,estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo') ENGINE=InnoDB");
        $this->db->exec("CREATE TABLE IF NOT EXISTS ventas (id_venta INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,id_cliente INT UNSIGNED NOT NULL,fecha_venta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,total DECIMAL(12,2) NOT NULL DEFAULT 0,estado ENUM('Pendiente','Pagada','Cancelada') NOT NULL DEFAULT 'Pendiente',CONSTRAINT fk_ventas_cliente_reportes FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)) ENGINE=InnoDB");
        $this->db->exec("CREATE TABLE IF NOT EXISTS venta_detalle (id_detalle INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,id_venta INT UNSIGNED NOT NULL,codigo_producto INT NOT NULL,cantidad INT UNSIGNED NOT NULL,precio_unitario DECIMAL(12,2) NOT NULL,subtotal DECIMAL(12,2) NOT NULL,CONSTRAINT fk_detalle_venta_reportes FOREIGN KEY (id_venta) REFERENCES ventas(id_venta) ON DELETE CASCADE) ENGINE=InnoDB");
    }
    public function activos(): array
    {
        $this->ensureTables();
        return $this->db->query("SELECT id_cliente,nombre,apellido FROM clientes WHERE estado='activo' ORDER BY nombre,apellido")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function crearRapido(string $nombreCompleto): int
    {
        $parts = preg_split('/\s+/', trim($nombreCompleto), 2);
        $stmt = $this->db->prepare('INSERT INTO clientes (nombre, apellido) VALUES (?, ?)');
        $stmt->execute([$parts[0], $parts[1] ?? 'Cliente']);
        return (int) $this->db->lastInsertId();
    }
}

class VentaModel
{
    private PDO $db;
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? (new Conexion())->conn;
        (new ClienteModel($this->db))->ensureTables();
    }

    public function crear(int $cliente, int $producto, int $cantidad, float $precio, string $fecha, float $total, string $estado): string
    {
        $check = $this->db->prepare('SELECT COUNT(*) FROM productos WHERE PRO_codigo=?');
        $check->execute([$producto]);
        if ($cliente < 1 || !(bool) $check->fetchColumn() || $cantidad < 1 || $precio < 0 || $fecha === '' || $total < 0 || !in_array($estado, ['Pendiente', 'Pagada', 'Cancelada'], true)) {
            throw new RuntimeException('Completa comprador, producto, cantidad, precio, fecha y estado.');
        }
        $this->db->beginTransaction();
        $stmt = $this->db->prepare('INSERT INTO ventas (id_cliente, fecha_venta, total, estado) VALUES (?, ?, ?, ?)');
        $stmt->execute([$cliente, $fecha, $total, $estado]);
        $id = (int) $this->db->lastInsertId();
        $stmt = $this->db->prepare('INSERT INTO venta_detalle (id_venta, codigo_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$id, $producto, $cantidad, $precio, $cantidad * $precio]);
        $this->db->commit();
        return 'Venta registrada correctamente.';
    }

    public function actualizar(int $idVenta, int $cliente, string $fecha, float $total, string $estado): string
    {
        $stmt = $this->db->prepare('UPDATE ventas SET id_cliente=?, fecha_venta=?, total=?, estado=? WHERE id_venta=?');
        $stmt->execute([$cliente, $fecha, $total, $estado, $idVenta]);
        return 'Venta actualizada correctamente.';
    }

    public function eliminar(int $idVenta): string
    {
        $stmt = $this->db->prepare('UPDATE ventas SET deleted_at=NOW() WHERE id_venta=? AND deleted_at IS NULL');
        try {
            $stmt->execute([$idVenta]);
        } catch (PDOException $e) {
            // Si la columna deleted_at aún no existe, crearla de forma compatible.
            $this->db->exec('ALTER TABLE ventas ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL');
            $stmt->execute([$idVenta]);
        }
        return $stmt->rowCount() > 0 ? 'Venta eliminada correctamente.' : 'La venta no existe o ya fue eliminada.';
    }

    /** Lista con filtros de fecha + texto (cliente/producto/estado), con paginación opcional. */
    public function listar(string $desde = '', string $hasta = '', string $q = '', ?int $limit = null, int $offset = 0): array
    {
        [$conds, $params] = $this->filtros($desde, $hasta, $q);
        $sql = "SELECT v.id_venta,v.id_cliente,v.fecha_venta,v.total,v.estado,CONCAT(c.nombre,' ',c.apellido) AS cliente, d.codigo_producto,d.cantidad,d.precio_unitario, p.PRO_nombre_producto AS producto_nombre FROM ventas v LEFT JOIN clientes c ON c.id_cliente=v.id_cliente LEFT JOIN venta_detalle d ON d.id_venta=v.id_venta LEFT JOIN productos p ON p.PRO_codigo=d.codigo_producto WHERE " . implode(' AND ', $conds) . ' ORDER BY v.fecha_venta DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contar(string $desde = '', string $hasta = '', string $q = ''): int
    {
        [$conds, $params] = $this->filtros($desde, $hasta, $q);
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM ventas v LEFT JOIN clientes c ON c.id_cliente=v.id_cliente LEFT JOIN venta_detalle d ON d.id_venta=v.id_venta WHERE ' . implode(' AND ', $conds));
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private function filtros(string $desde, string $hasta, string $q): array
    {
        $conds = ['v.deleted_at IS NULL'];
        $params = [];
        if ($desde !== '') {
            $conds[] = 'v.fecha_venta >= ?';
            $params[] = $desde . ' 00:00:00';
        }
        if ($hasta !== '') {
            $conds[] = 'v.fecha_venta <= ?';
            $params[] = $hasta . ' 23:59:59';
        }
        $q = trim(preg_replace('/\s+/', ' ', $q));
        if ($q !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $conds[] = '(CONCAT(COALESCE(c.nombre,"")," ",COALESCE(c.apellido,"")) LIKE ? OR CAST(d.codigo_producto AS CHAR) LIKE ? OR COALESCE(p.PRO_nombre_producto,"") LIKE ? OR v.estado LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        return [$conds, $params];
    }

    public function recientes(int $limit = 8): array
    {
        try {
            return $this->db->query(
                'SELECT v.id_venta, v.fecha_venta, v.total, v.estado, CONCAT(c.nombre, \' \', c.apellido) AS cliente FROM ventas v LEFT JOIN clientes c ON c.id_cliente=v.id_cliente WHERE v.deleted_at IS NULL ORDER BY v.fecha_venta DESC LIMIT ' . (int) $limit
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }
}
