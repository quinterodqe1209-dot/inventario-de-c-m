<?php
// controllers/ClienteController.php (NUEVO) — portal cliente: catálogo, favoritos, PQRS, facturas.
// Antes todo esto vivía dentro de views/clientes_dashboard.php.

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/ProductoModel.php';
require_once __DIR__ . '/../models/ClientePortalModel.php';
require_once __DIR__ . '/../models/ComprobantePagoModel.php';
require_once __DIR__ . '/../models/PqrsModel.php';

class ClienteController extends BaseController
{
    private ProductoModel $productos;
    private ClientePortalModel $portal;
    private ComprobantePagoModel $pagos;
    private PqrsModel $pqrs;

    public function __construct()
    {
        $db = $this->db();
        $this->productos = new ProductoModel($db);
        $this->portal = new ClientePortalModel($db);
        $this->pagos = new ComprobantePagoModel($db);
        $this->pqrs = new PqrsModel($db);
    }

    public function canAccess(): bool
    {
        return in_array($_SESSION['rol'] ?? '', ['cliente', 'gerente', 'admin'], true);
    }

    public function toggleFavorito(int $productoId): string
    {
        $doc = trim((string) ($_SESSION['user']['documento_id'] ?? ''));
        if ($doc === '' || $productoId <= 0) {
            return '';
        }
        return $this->portal->toggleFavorito($doc, $productoId);
    }

    public function crearPqrs(string $tipo, string $descripcion): array
    {
        $tipos = ['Queja sobre producto', 'Reclamo por entrega', 'Sugerencia'];
        if (!in_array($tipo, $tipos, true) || $descripcion === '') {
            return ['ok' => false, 'msg' => 'Selecciona un tipo y escribe la descripción de tu solicitud.'];
        }
        $this->pqrs->create($this->uid(), $tipo, $descripcion);
        return ['ok' => true, 'msg' => 'Tu PQRS fue enviada correctamente.'];
    }

    /** Arma todas las variables que la vista espera (mismos nombres que antes + paginación catálogo). */
    public function datos(string $q = '', int $page = 1, int $perPage = 9): array
    {
        $page = max(1, $page);
        $total = $this->productos->contar($q);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $catalogProducts = $this->productos->catalog($q, $perPage, ($page - 1) * $perPage);
        $stockByName = [];
        foreach ($catalogProducts as $p) {
            $stockByName[$p['PRO_nombre_producto']] = (int) $p['PRO_stock_actual'];
        }
        $doc = trim((string) ($_SESSION['user']['documento_id'] ?? ''));
        $favoriteProductIds = $this->portal->favoritos($doc);
        $paymentProofs = $this->pagos->forUser($this->uid(), 10);
        $pqrs = $this->pqrs->mine($this->uid());
        $fac = $this->portal->facturas($doc);
        $resueltas = 0;
        foreach ($pqrs as $item) {
            if (($item['estado'] ?? '') === 'Resuelta') {
                $resueltas++;
            }
        }
        return [
            'catalogProducts' => $catalogProducts, 'stockByName' => $stockByName,
            'favoriteProductIds' => $favoriteProductIds, 'favoriteMessage' => '',
            'paymentProofs' => $paymentProofs,
            'paymentMessage' => trim($_GET['mensaje_pago'] ?? ''),
            'paymentAmount' => max(0, (float) ($_GET['total_pago'] ?? 0)),
            'paymentInvoice' => trim($_GET['factura_pago'] ?? ''),
            'pqrs' => $pqrs, 'pqrsMessage' => '', 'pqrsError' => '',
            'kpiComprasMes' => $fac['comprasMes'], 'misFacturas' => $fac['facturas'],
            'facturasPendientes' => $fac['pendientes'],
            'mqrsTotal' => count($pqrs), 'pqrsResueltas' => $resueltas,
            'busquedaCatalogo' => $q,
            'paginaCatalogo' => $page, 'totalPaginasCatalogo' => $totalPages, 'totalCatalogo' => $total,
        ];
    }
}
