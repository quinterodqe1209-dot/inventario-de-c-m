<?php
// models/ComprobantePagoModel.php
// Antes: SQL + DDL dispersos en index.php, views/gerente_sbadm.php y views/clientes_dashboard.php.

require_once __DIR__ . '/../config/conexion.php';

class ComprobantePagoModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? (new Conexion())->conn;
    }

    public function ensureTable(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $this->db->exec("CREATE TABLE IF NOT EXISTS comprobantes_pago (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            medio_pago ENUM('Nequi','Bancolombia','Davivienda','Banco de la Vivienda') NOT NULL,
            monto DECIMAL(12,2) NOT NULL,
            referencia VARCHAR(100) NOT NULL,
            comprobante VARCHAR(255) NOT NULL,
            estado ENUM('Pendiente','Aprobado','Rechazado') NOT NULL DEFAULT 'Pendiente',
            fecha_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_comprobantes_estado (estado),
            INDEX idx_comprobantes_usuario (id_usuario)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->exec("ALTER TABLE comprobantes_pago ADD COLUMN IF NOT EXISTS direccion_envio VARCHAR(200) NOT NULL DEFAULT '' AFTER referencia");
        $this->db->exec("ALTER TABLE comprobantes_pago ADD COLUMN IF NOT EXISTS numero_factura VARCHAR(50) NOT NULL DEFAULT '' AFTER direccion_envio");
        $this->db->exec("ALTER TABLE comprobantes_pago MODIFY medio_pago ENUM('Nequi','Bancolombia','Davivienda','Banco de la Vivienda') NOT NULL");
    }

    public function create(int $uid, string $medio, float $monto, string $ref, string $dir, string $factura, string $ruta): void
    {
        $this->ensureTable();
        $stmt = $this->db->prepare(
            'INSERT INTO comprobantes_pago (id_usuario, medio_pago, monto, referencia, direccion_envio, numero_factura, comprobante) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$uid, $medio, $monto, $ref, $dir, $factura, $ruta]);
    }

    public function setEstado(int $id, string $estado): void
    {
        $stmt = $this->db->prepare('UPDATE comprobantes_pago SET estado = ? WHERE id = ?');
        $stmt->execute([$estado, $id]);
    }

    public function forGerente(int $limit = 30): array
    {
        $this->ensureTable();
        return $this->db->query(
            "SELECT cp.id, cp.medio_pago, cp.monto, cp.referencia, cp.direccion_envio, cp.numero_factura, cp.comprobante, cp.estado, cp.fecha_envio, COALESCE(CONCAT(u.nombre, ' ', u.apellido), u.username, 'Cliente') AS cliente FROM comprobantes_pago cp LEFT JOIN usuarios u ON u.id = cp.id_usuario ORDER BY cp.fecha_envio DESC LIMIT " . (int) $limit
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function forUser(int $uid, int $limit = 10): array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare(
            'SELECT id, medio_pago, monto, referencia, direccion_envio, numero_factura, estado, fecha_envio FROM comprobantes_pago WHERE id_usuario = ? ORDER BY fecha_envio DESC LIMIT ' . (int) $limit
        );
        $stmt->execute([$uid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
