<?php
// controllers/ReporteController.php (NUEVO) — ventas + CSV/PDF (antes en views/reportes.php).

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/VentaModel.php';

class ReporteController extends BaseController
{
    private VentaModel $ventas;
    private ClienteModel $clientes;

    public function __construct()
    {
        $db = $this->db();
        $this->ventas = new VentaModel($db);
        $this->clientes = new ClienteModel($db);
    }

    public function canEdit(): bool
    {
        return in_array($_SESSION['rol'] ?? '', ['gerente', 'admin'], true);
    }

    public function datos(string $desde = '', string $hasta = '', string $q = '', int $page = 1, int $perPage = 10): array
    {
        $page = max(1, $page);
        $total = $this->ventas->contar($desde, $hasta, $q);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $sales = $this->ventas->listar($desde, $hasta, $q, $perPage, ($page - 1) * $perPage);
        return [
            'clients' => $this->clientes->activos(),
            'products' => $this->productosParaVenta(),
            'sales' => $sales,
            'totalSales' => array_sum(array_map(fn($s) => $s['estado'] === 'Cancelada' ? 0 : (float) $s['total'], $sales)),
            'fechaDesde' => $desde, 'fechaHasta' => $hasta,
            'busquedaVentas' => $q,
            'paginaActual' => $page, 'totalPaginas' => $totalPages, 'totalVentas' => $total,
            'canEdit' => $this->canEdit(), 'message' => '', 'error' => '',
        ];
    }

    /** Listado completo (sin paginar) para exportaciones CSV/PDF. */
    public function listadoCompleto(string $desde = '', string $hasta = '', string $q = ''): array
    {
        $sales = $this->ventas->listar($desde, $hasta, $q);
        return [
            'sales' => $sales,
            'totalSales' => array_sum(array_map(fn($s) => $s['estado'] === 'Cancelada' ? 0 : (float) $s['total'], $sales)),
        ];
    }

    private function productosParaVenta(): array
    {
        return $this->db()->query(
            'SELECT PRO_codigo,PRO_nombre_producto,PRO_precio_unitario FROM productos WHERE deleted_at IS NULL ORDER BY PRO_nombre_producto'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Procesa sale_action=create/update/delete. Devuelve mensaje o lanza. */
    public function guardar(array $post): string
    {
        $action = $post['sale_action'] ?? '';
        $cliente = (int) ($post['id_cliente'] ?? 0);
        if ($cliente < 1 && trim($post['cliente_nuevo'] ?? '') !== '') {
            $cliente = $this->clientes->crearRapido(trim($post['cliente_nuevo']));
        }
        if (in_array($action, ['create', 'update'], true)) {
            $producto = (int) ($post['codigo_producto'] ?? 0);
            $cantidad = filter_var($post['cantidad'] ?? null, FILTER_VALIDATE_INT);
            $precio = filter_var($post['precio_unitario'] ?? null, FILTER_VALIDATE_FLOAT);
            $fecha = trim($post['fecha_venta'] ?? '');
            $total = filter_var($post['total'] ?? null, FILTER_VALIDATE_FLOAT);
            $estado = $post['estado'] ?? 'Pendiente';
            if ($action === 'create') {
                return $this->ventas->crear($cliente, $producto, (int) $cantidad, (float) $precio, $fecha, (float) $total, $estado);
            }
            return $this->ventas->actualizar((int) ($post['id_venta'] ?? 0), $cliente, $fecha, (float) $total, $estado);
        }
        if ($action === 'delete') {
            return $this->ventas->eliminar((int) ($post['id_venta'] ?? 0));
        }
        return '';
    }

    public function exportarCsv(array $sales): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="reporte_ventas_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['ID Venta', 'Comprador', 'Producto', 'Código', 'Cantidad', 'Precio unitario', 'Fecha', 'Total', 'Estado']);
        foreach ($sales as $sale) {
            fputcsv($out, [$sale['id_venta'], $sale['cliente'] ?? 'Sin cliente', $sale['producto_nombre'] ?? 'Sin detalle', $sale['codigo_producto'] ?? '-', $sale['cantidad'] ?? 0, $sale['precio_unitario'] ?? 0, $sale['fecha_venta'], $sale['total'], $sale['estado']]);
        }
        fclose($out);
        exit();
    }

    public function exportarPdf(array $sales, float $totalSales, string $desde, string $hasta): void
    {
        require_once dirname(__DIR__) . '/vendor/fpdf/fpdf.php';
        if (!function_exists('pdf_txt')) {
            eval('function pdf_txt(string $t): string { return mb_convert_encoding($t, "ISO-8859-1", "UTF-8"); }');
        }
        if (!class_exists('ReportePDFExport', false)) {
            eval('class ReportePDFExport extends FPDF {
                public string $subtitulo = "";
                public function Header() {
                    $this->SetFont("Arial", "B", 14);
                    $this->Cell(0, 8, mb_convert_encoding("C&M Soluciones Abrasivas SAS", "ISO-8859-1", "UTF-8"), 0, 1, "C");
                    $this->SetFont("Arial", "", 10);
                    $this->Cell(0, 6, mb_convert_encoding("Reporte de ventas", "ISO-8859-1", "UTF-8"), 0, 1, "C");
                    if ($this->subtitulo !== "") { $this->Cell(0, 6, mb_convert_encoding($this->subtitulo, "ISO-8859-1", "UTF-8"), 0, 1, "C"); }
                    $this->Ln(4);
                }
                public function Footer() {
                    $this->SetY(-15);
                    $this->SetFont("Arial", "I", 8);
                    $this->Cell(0, 10, "Pagina " . $this->PageNo() . " de {nb}", 0, 0, "C");
                }
            }');
        }
        $rango = 'Generado el ' . date('d/m/Y H:i');
        if ($desde !== '' || $hasta !== '') {
            $rango .= ' - Periodo: ' . ($desde ?: 'inicio') . ' a ' . ($hasta ?: 'hoy');
        }
        $pdf = new ReportePDFExport();
        $pdf->subtitulo = $rango;
        $pdf->AliasNbPages();
        $pdf->AddPage('L');
        $pdf->SetFont('Arial', 'B', 9);
        foreach (['ID' => 12, 'Comprador' => 35, 'Producto' => 55, 'Cant.' => 12, 'P. Unit.' => 22, 'Fecha' => 30, 'Total' => 25, 'Estado' => 25] as $t => $w) {
            $pdf->Cell($w, 8, pdf_txt($t), 1, 0, 'C');
        }
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8);
        foreach ($sales as $sale) {
            $nombreProd = trim((string) ($sale['producto_nombre'] ?? ''));
            if ($nombreProd === '') {
                $nombreProd = 'PRO-' . ($sale['codigo_producto'] ?? '-');
            }
            $pdf->Cell(12, 7, (string) $sale['id_venta'], 1);
            $pdf->Cell(35, 7, pdf_txt(mb_substr($sale['cliente'] ?? 'Sin cliente', 0, 24)), 1);
            $pdf->Cell(55, 7, pdf_txt(mb_substr($nombreProd . ' (#' . ($sale['codigo_producto'] ?? '-') . ')', 0, 42)), 1);
            $pdf->Cell(12, 7, (string) ($sale['cantidad'] ?? 0), 1, 0, 'C');
            $pdf->Cell(22, 7, '$ ' . number_format((float) ($sale['precio_unitario'] ?? 0), 0, ',', '.'), 1, 0, 'R');
            $pdf->Cell(30, 7, (string) $sale['fecha_venta'], 1, 0, 'C');
            $pdf->Cell(25, 7, '$ ' . number_format((float) $sale['total'], 0, ',', '.'), 1, 0, 'R');
            $pdf->Cell(25, 7, pdf_txt($sale['estado']), 1, 0, 'C');
            $pdf->Ln();
        }
        if (!$sales) {
            $pdf->Cell(216, 8, pdf_txt('No hay ventas en el periodo seleccionado.'), 1, 1, 'C');
        }
        $pdf->Ln(4);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 8, pdf_txt('Total vendido: $ ' . number_format($totalSales, 0, ',', '.')), 0, 1, 'R');
        $pdf->Output('D', 'reporte_ventas_' . date('Y-m-d') . '.pdf');
        exit();
    }
}
