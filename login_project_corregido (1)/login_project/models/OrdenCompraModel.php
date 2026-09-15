<?php
// models/OrdenCompraModel.php + Despacho + Factura orden (antes en index.php POST).

require_once __DIR__ . '/../config/conexion.php';

class OrdenCompraModel
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
        $this->db->exec("CREATE TABLE IF NOT EXISTS orden_compra (ORD_id_orden INT NOT NULL, PVR_contacto VARCHAR(12) NOT NULL, ORD_fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ORD_estado VARCHAR(20) NOT NULL DEFAULT 'pendiente', ORD_total DECIMAL(10,2) DEFAULT NULL, ORD_retrasada TINYINT(1) NOT NULL DEFAULT 0, ORD_notas TEXT NULL) ENGINE=InnoDB");
        $this->db->exec("ALTER TABLE productos ADD COLUMN IF NOT EXISTS PRO_proveedor VARCHAR(150) NOT NULL DEFAULT 'Proveedor por asignar'");
        $this->db->exec("CREATE TABLE IF NOT EXISTS detalle_orden_compra (DOC_id INT NOT NULL, ORD_id_orden INT NOT NULL, PRO_codigo INT NOT NULL, DOC_cantidad INT NOT NULL, DOC_precio_unitario DECIMAL(10,2) NOT NULL) ENGINE=InnoDB");
        $this->db->exec("CREATE TABLE IF NOT EXISTS despacho_bodega (DES_id INT NOT NULL, DES_direccion_envio VARCHAR(150) NOT NULL, DES_orden_bodega VARCHAR(50) NOT NULL, DES_id_confirmacion VARCHAR(50) NOT NULL, AUX_id INT DEFAULT NULL, estado VARCHAR(20) DEFAULT 'Pendiente', PED_id_pedido INT NOT NULL) ENGINE=InnoDB");
        $this->db->exec("CREATE TABLE IF NOT EXISTS factura_orden_compra (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ORD_id_orden INT NOT NULL, archivo VARCHAR(255) NOT NULL, fecha_subida DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY idx_foc_orden (ORD_id_orden)) ENGINE=InnoDB");
    }

    /** Genera orden de reabastecimiento. Devuelve mensaje (mismo texto que index.php). */
    public function generarReabastecimiento(array $producto): string
    {
        $this->ensureTables();
        $cantidad = max(1, (int) $producto['stock_objetivo'] - (int) $producto['PRO_stock_actual']);
        $ordenId = (int) $this->db->query('SELECT COALESCE(MAX(ORD_id_orden), 0) + 1 FROM orden_compra')->fetchColumn();
        $detalleId = (int) $this->db->query('SELECT COALESCE(MAX(DOC_id), 0) + 1 FROM detalle_orden_compra')->fetchColumn();
        $total = $cantidad * (float) $producto['PRO_costo_base'];
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO orden_compra (ORD_id_orden, PVR_contacto, ORD_estado, ORD_total, ORD_notas) VALUES (?, ?, 'pendiente', ?, ?)");
            $stmt->execute([$ordenId, substr((string) $producto['PRO_proveedor'], 0, 12), $total, 'Reabastecimiento automático por stock bajo.']);
            $stmt = $this->db->prepare('INSERT INTO detalle_orden_compra (DOC_id, ORD_id_orden, PRO_codigo, DOC_cantidad, DOC_precio_unitario) VALUES (?, ?, ?, ?, ?)');
            // PRO_codigo se pasa desde el llamador vía $producto['PRO_codigo']
            $stmt->execute([$detalleId, $ordenId, (int) ($producto['PRO_codigo'] ?? 0), $cantidad, $producto['PRO_costo_base']]);
            $this->db->commit();
            return "Orden OC-$ordenId generada para {$producto['PRO_nombre_producto']}.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return 'No fue posible generar la orden de reabastecimiento.';
        }
    }

    public function actualizarEstado(int $ordenId, string $estado, int $retrasada, ?string $notas): string
    {
        $this->ensureTables();
        if ($ordenId <= 0) {
            return 'Órden inválida.';
        }
        try {
            $stmt = $this->db->prepare('UPDATE orden_compra SET ORD_estado = :estado, ORD_retrasada = :retrasada, ORD_notas = :notas WHERE ORD_id_orden = :id');
            $stmt->execute([':estado' => $estado, ':retrasada' => $retrasada, ':notas' => $notas !== '' ? $notas : null, ':id' => $ordenId]);
            return $stmt->rowCount() > 0 ? "OC-$ordenId actualizada a estado '$estado'." : 'La órden no existe o no cambió de estado.';
        } catch (Throwable $e) {
            return 'Error al actualizar la órden.';
        }
    }

    public function agendarEntrega(string $ordenRaw, int $ordenId, string $fecha, string $destino): string
    {
        $this->ensureTables();
        if ($ordenId <= 0 || $fecha === '' || $destino === '') {
            return 'Completa la orden, la fecha y el destino.';
        }
        try {
            $nextId = (int) $this->db->query('SELECT COALESCE(MAX(DES_id),0)+1 FROM despacho_bodega')->fetchColumn();
            $confirmacion = 'CONFIRM-' . strtoupper(bin2hex(random_bytes(5)));
            $estado = (strtotime($fecha) <= strtotime(date('Y-m-d'))) ? 'Enviado' : 'Agendado';
            $stmt = $this->db->prepare('INSERT INTO despacho_bodega (DES_id, DES_direccion_envio, DES_orden_bodega, DES_id_confirmacion, AUX_id, estado, PED_id_pedido) VALUES (?,?,?,?,NULL,?,0)');
            $stmt->execute([$nextId, $destino, $ordenRaw, $confirmacion, $estado]);
            return "Entrega para $ordenRaw agendada (" . date('d/m/Y', strtotime($fecha)) . ") a $destino.";
        } catch (Throwable $e) {
            return 'Error al agendar la entrega.';
        }
    }

    public function guardarFactura(int $ordenId, string $rutaRelativa): void
    {
        $this->ensureTables();
        $stmt = $this->db->prepare('INSERT INTO factura_orden_compra (ORD_id_orden, archivo) VALUES (?,?)');
        $stmt->execute([$ordenId, $rutaRelativa]);
    }

    /** Lista órdenes con su detalle (para el panel real de proveedores). */
    public function listar(int $limit = 50): array
    {
        $this->ensureTables();
        try {
            $stmt = $this->db->query(
                'SELECT o.ORD_id_orden, o.PVR_contacto, o.ORD_fecha, o.ORD_estado, o.ORD_total, o.ORD_retrasada, o.ORD_notas, d.PRO_codigo, d.DOC_cantidad, d.DOC_precio_unitario, p.PRO_nombre_producto FROM orden_compra o LEFT JOIN detalle_orden_compra d ON d.ORD_id_orden = o.ORD_id_orden LEFT JOIN productos p ON p.PRO_codigo = d.PRO_codigo ORDER BY o.ORD_fecha DESC LIMIT ' . (int) $limit
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /** Productos con stock bajo (para reabastecimiento real). */
    public function stockBajo(int $limit = 50): array
    {
        try {
            $stmt = $this->db->query(
                'SELECT PRO_codigo, PRO_nombre_producto, PRO_stock_actual, PRO_stock_minimo, COALESCE(PRO_stock_maximo, PRO_stock_minimo * 2) AS stock_objetivo, PRO_costo_base, PRO_proveedor FROM productos WHERE deleted_at IS NULL AND PRO_stock_actual <= PRO_stock_minimo ORDER BY PRO_stock_actual ASC LIMIT ' . (int) $limit
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function despachos(int $limit = 30): array
    {
        $this->ensureTables();
        try {
            return $this->db->query('SELECT DES_id, DES_direccion_envio, DES_orden_bodega, DES_id_confirmacion, estado FROM despacho_bodega ORDER BY DES_id DESC LIMIT ' . (int) $limit)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function facturas(int $limit = 30): array
    {
        $this->ensureTables();
        try {
            return $this->db->query('SELECT ORD_id_orden, archivo, fecha_subida FROM factura_orden_compra ORDER BY fecha_subida DESC LIMIT ' . (int) $limit)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }
}
