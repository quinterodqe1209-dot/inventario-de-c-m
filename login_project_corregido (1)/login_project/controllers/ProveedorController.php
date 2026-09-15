<?php
// controllers/ProveedorController.php — órdenes, entregas y facturas (antes en index.php).

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/ProductoModel.php';
require_once __DIR__ . '/../models/OrdenCompraModel.php';

class ProveedorController extends BaseController
{
    private ProductoModel $productos;
    private OrdenCompraModel $ordenes;

    public function __construct()
    {
        $db = $this->db();
        $this->productos = new ProductoModel($db);
        $this->ordenes = new OrdenCompraModel($db);
    }

    public function canManageOrders(): bool
    {
        return in_array($_SESSION['rol'] ?? '', ['proveedor', 'gerente', 'admin'], true);
    }

    public function canGenerateOrders(): bool
    {
        return in_array($_SESSION['rol'] ?? '', ['gerente', 'admin'], true);
    }

    public function generarReabastecimiento(int $productoId): string
    {
        $producto = $this->productos->findForOrder($productoId);
        if (!$producto) {
            return 'Producto no encontrado.';
        }
        $producto['PRO_codigo'] = $productoId;
        return $this->ordenes->generarReabastecimiento($producto);
    }

    public function actualizarOrden(array $post): string
    {
        $ordenId = (int) preg_replace('/[^0-9]/', '', trim($post['orden_id'] ?? ''));
        $grano = trim($post['avance'] ?? 'P40');
        $mapa = ['P40' => 'pendiente', 'P80' => 'confirmado', 'P120' => 'en produccion', 'P180' => 'en transito', 'P220' => 'entregado'];
        $estado = $mapa[$grano] ?? 'pendiente';
        $retrasada = (isset($post['retrasada']) && $post['retrasada'] === '1') ? 1 : 0;
        $notas = trim($post['notas'] ?? '');
        return $this->ordenes->actualizarEstado($ordenId, $estado, $retrasada, $notas);
    }

    public function agendarEntrega(array $post): string
    {
        $ordenRaw = trim($post['orden'] ?? '');
        $ordenId = (int) preg_replace('/[^0-9]/', '', $ordenRaw);
        return $this->ordenes->agendarEntrega($ordenRaw, $ordenId, trim($post['fecha'] ?? ''), trim($post['destino'] ?? ''));
    }

    /** Valida y guarda factura PDF/XML. Devuelve mensaje (mismo texto que index.php). */
    public function subirFactura(array $post, array $file): string
    {
        $ordenId = (int) preg_replace('/[^0-9]/', '', trim($post['orden_id'] ?? ''));
        $msg = 'Archivo inválido: usa PDF o XML de hasta 5 MB.';
        if (!($ordenId > 0 && isset($file['error']) && (int) $file['error'] === UPLOAD_ERR_OK && (int) $file['size'] > 0 && (int) $file['size'] <= 5242880)) {
            return $msg;
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'xml'], true)) {
            return $msg;
        }
        try {
            $dir = dirname(__DIR__) . '/uploads/facturas';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $nombre = 'factura_OC' . $ordenId . '_' . date('Ymd_His') . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $dir . DIRECTORY_SEPARATOR . $nombre)) {
                $this->ordenes->guardarFactura($ordenId, 'uploads/facturas/' . $nombre);
                return "Factura de OC-$ordenId subida correctamente.";
            }
        } catch (Throwable $e) {
            return 'Error al guardar la factura.';
        }
        return 'Error al guardar la factura.';
    }

    /** Datos reales para el panel (vista + API JSON). */
    public function datosPanel(): array
    {
        $stockBajo = $this->ordenes->stockBajo(50);
        $ordenes = $this->ordenes->listar(50);
        return [
            'stockBajo' => $stockBajo,
            'ordenes' => $ordenes,
            'despachos' => $this->ordenes->despachos(30),
            'facturas' => $this->ordenes->facturas(30),
            'totalOrdenes' => count($ordenes),
            'totalAlertas' => count($stockBajo),
            'msg' => trim($_GET['msg'] ?? ''),
            'rol' => $_SESSION['rol'] ?? 'proveedor',
            'username' => $_SESSION['user']['username'] ?? 'Proveedor',
        ];
    }

    public function apiData(): array
    {
        $d = $this->datosPanel();
        // Productos reales mapeados al formato que el React ya entiende.
        $productos = array_map(static function (array $p, int $i): array {
            $stock = (int) $p['PRO_stock_actual'];
            $min = max(1, (int) $p['PRO_stock_minimo']);
            return [
                'id' => 'real-' . (int) $p['PRO_codigo'],
                'codigoReal' => (int) $p['PRO_codigo'],
                'sku' => 'PRO-' . (int) $p['PRO_codigo'],
                'descripcion' => $p['PRO_nombre_producto'] ?? ('Producto ' . $p['PRO_codigo']),
                'proveedorId' => 'real-prov',
                'stockActual' => $stock,
                'stockMinimo' => $min,
                'stockOptimo' => (int) ($p['stock_objetivo'] ?? ($min * 2)),
                'costoUnitarioCOP' => (float) ($p['PRO_costo_base'] ?? 0),
                'consumoPromedioDiario' => 1,
            ];
        }, $d['stockBajo'], array_keys($d['stockBajo']));
        return [
            'productosReales' => $productos,
            'totalAlertasReales' => count($productos),
            'totalOrdenesReales' => $d['totalOrdenes'],
            'ordenesReales' => array_slice($d['ordenes'], 0, 20),
        ];
    }
}
