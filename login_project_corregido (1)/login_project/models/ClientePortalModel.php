<?php
// models/ClientePortalModel.php — favoritos, perfil_cliente, facturas del cliente.
// Antes en views/clientes_dashboard.php y views/perfil.php.

require_once __DIR__ . '/../config/conexion.php';

class ClientePortalModel
{
    private PDO $db;
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? (new Conexion())->conn;
    }

    public function toggleFavorito(string $usuarioDoc, int $productoId): string
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM auditoria_favoritos WHERE usuario = :u AND producto = :p');
        $stmt->execute([':u' => $usuarioDoc, ':p' => $productoId]);
        if ((int) $stmt->fetchColumn() > 0) {
            $stmt = $this->db->prepare('DELETE FROM auditoria_favoritos WHERE usuario = :u AND producto = :p');
            $stmt->execute([':u' => $usuarioDoc, ':p' => $productoId]);
            return 'Producto retirado de favoritos.';
        }
        $stmt = $this->db->prepare('INSERT INTO auditoria_favoritos (usuario, producto, fecha) VALUES (:u, :p, NOW())');
        $stmt->execute([':u' => $usuarioDoc, ':p' => $productoId]);
        return 'Producto agregado a favoritos.';
    }

    public function favoritos(string $usuarioDoc): array
    {
        try {
            $stmt = $this->db->prepare('SELECT producto FROM auditoria_favoritos WHERE usuario = :u');
            $stmt->execute([':u' => $usuarioDoc]);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable $e) {
            return [];
        }
    }

    public function favoritosDetalle(string $usuarioDoc): array
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT p.PRO_nombre_producto, p.PRO_marca, p.PRO_precio_unitario FROM auditoria_favoritos af INNER JOIN productos p ON p.PRO_codigo = af.producto WHERE af.usuario = :u ORDER BY af.fecha DESC'
            );
            $stmt->execute([':u' => $usuarioDoc]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function perfilCliente(string $doc): array
    {
        $base = ['USU_documento_identidad' => '', 'PFL_historial_compra' => '', 'PFL_productos_favoritos' => '', 'PFL_fecha_compra' => '', 'PFL_estado_pedidos' => ''];
        try {
            $stmt = $this->db->prepare(
                'SELECT USU_documento_identidad, PFL_historial_compra, PFL_productos_favoritos, PFL_fecha_compra, PFL_estado_pedidos FROM perfil_cliente WHERE USU_documento_identidad = :doc LIMIT 1'
            );
            $stmt->execute([':doc' => $doc]);
            return array_merge($base, $stmt->fetch(PDO::FETCH_ASSOC) ?: []);
        } catch (Throwable $e) {
            $base['USU_documento_identidad'] = $doc;
            return $base;
        }
    }

    /** KPIs y facturas del cliente (mismo SQL que la vista). */
    public function facturas(string $doc): array
    {
        $out = ['comprasMes' => 0.0, 'facturas' => [], 'pendientes' => 0];
        if ($doc === '') {
            return $out;
        }
        try {
            $s = $this->db->prepare(
                'SELECT COALESCE(SUM(fp.FAC_total),0) AS total FROM factura_pedido fp INNER JOIN pedido p ON p.PED_id_pedido = fp.PED_id_pedido WHERE p.USU_documento_identidad = :doc AND MONTH(fp.FAC_fecha) = MONTH(CURDATE()) AND YEAR(fp.FAC_fecha) = YEAR(CURDATE())'
            );
            $s->execute([':doc' => $doc]);
            $out['comprasMes'] = (float) ($s->fetchColumn() ?: 0);
            $s = $this->db->prepare(
                'SELECT fp.FAC_id_factura, fp.FAC_numero_factura, fp.FAC_fecha, fp.FAC_tipo_pago, fp.FAC_direccion_envio, fp.FAC_total, p.PED_estado FROM factura_pedido fp INNER JOIN pedido p ON p.PED_id_pedido = fp.PED_id_pedido WHERE p.USU_documento_identidad = :doc ORDER BY fp.FAC_fecha DESC LIMIT 20'
            );
            $s->execute([':doc' => $doc]);
            $out['facturas'] = $s->fetchAll(PDO::FETCH_ASSOC);
            $s = $this->db->prepare(
                "SELECT COUNT(*) FROM factura_pedido fp INNER JOIN pedido p ON p.PED_id_pedido = fp.PED_id_pedido WHERE p.USU_documento_identidad = :doc AND p.PED_estado = 'pendiente'"
            );
            $s->execute([':doc' => $doc]);
            $out['pendientes'] = (int) ($s->fetchColumn() ?: 0);
        } catch (Throwable $e) {
            // Tablas opcionales pueden no existir: se devuelven vacíos sin romper.
        }
        return $out;
    }
}
