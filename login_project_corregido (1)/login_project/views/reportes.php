<?php
require_once __DIR__ . '/../config/require_auth.php';
require_role(['gerente', 'admin']);

require_once __DIR__ . '/../config/conexion.php';
// MVC: el controlador (ReporteController) ya preparó $sales/$clients/$products.
// Este bloque legacy solo corre en acceso directo.
if (empty($__MVC_READY ?? null)) {
$db = (new Conexion())->conn;
$canEdit = in_array($_SESSION['rol'] ?? '', ['gerente'], true);
$message = $error = '';
$db->exec("CREATE TABLE IF NOT EXISTS clientes (id_cliente INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,nombre VARCHAR(100) NOT NULL,apellido VARCHAR(100) NOT NULL,documento VARCHAR(40) NULL UNIQUE,estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo') ENGINE=InnoDB");
$db->exec("CREATE TABLE IF NOT EXISTS ventas (id_venta INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,id_cliente INT UNSIGNED NOT NULL,fecha_venta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,total DECIMAL(12,2) NOT NULL DEFAULT 0,estado ENUM('Pendiente','Pagada','Cancelada') NOT NULL DEFAULT 'Pendiente',CONSTRAINT fk_ventas_cliente_reportes FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)) ENGINE=InnoDB");
$db->exec("CREATE TABLE IF NOT EXISTS venta_detalle (id_detalle INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,id_venta INT UNSIGNED NOT NULL,codigo_producto INT NOT NULL,cantidad INT UNSIGNED NOT NULL,precio_unitario DECIMAL(12,2) NOT NULL,subtotal DECIMAL(12,2) NOT NULL,CONSTRAINT fk_detalle_venta_reportes FOREIGN KEY (id_venta) REFERENCES ventas(id_venta) ON DELETE CASCADE) ENGINE=InnoDB");
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    csrf_verify();
    $action = $_POST['sale_action'] ?? '';
    try {
        $client = (int) ($_POST['id_cliente'] ?? 0);
        $newClient = trim($_POST['cliente_nuevo'] ?? '');
        if ($client < 1 && $newClient !== '') {
            $parts = preg_split('/\s+/', $newClient, 2);
            $stmt = $db->prepare('INSERT INTO clientes (nombre, apellido) VALUES (?, ?)');
            $stmt->execute([$parts[0], $parts[1] ?? 'Cliente']);
            $client = (int) $db->lastInsertId();
        }
        if (in_array($action, ['create', 'update'], true)) {
            $product = (int) ($_POST['codigo_producto'] ?? 0);
            $quantity = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_INT);
            $unitPrice = filter_var($_POST['precio_unitario'] ?? null, FILTER_VALIDATE_FLOAT);
            $date = trim($_POST['fecha_venta'] ?? '');
            $total = filter_var($_POST['total'] ?? null, FILTER_VALIDATE_FLOAT);
            $status = $_POST['estado'] ?? 'Pendiente';
            $productCheck = $db->prepare('SELECT COUNT(*) FROM productos WHERE PRO_codigo=?');
            $productCheck->execute([$product]);
            if ($client < 1 || !(bool) $productCheck->fetchColumn() || $quantity === false || $quantity < 1 || $unitPrice === false || $unitPrice < 0 || $date === '' || $total === false || $total < 0 || !in_array($status, ['Pendiente', 'Pagada', 'Cancelada'], true)) throw new RuntimeException('Completa comprador, producto, cantidad, precio, fecha y estado.');
            if ($action === 'create') {
                $db->beginTransaction();
                $stmt = $db->prepare('INSERT INTO ventas (id_cliente, fecha_venta, total, estado) VALUES (?, ?, ?, ?)');
                $stmt->execute([$client, $date, $total, $status]);
                $saleId = (int) $db->lastInsertId();
                $stmt = $db->prepare('INSERT INTO venta_detalle (id_venta, codigo_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$saleId, $product, $quantity, $unitPrice, $quantity * $unitPrice]);
                $db->commit();
                $message = 'Venta registrada correctamente.';
            } else {
                $stmt = $db->prepare('UPDATE ventas SET id_cliente=?, fecha_venta=?, total=?, estado=? WHERE id_venta=?');
                $stmt->execute([$client, $date, $total, $status, (int) ($_POST['id_venta'] ?? 0)]);
                $message = 'Venta actualizada correctamente.';
            }
        } elseif ($action === 'delete') {
            $stmt = $db->prepare('UPDATE ventas SET deleted_at=NOW() WHERE id_venta=? AND deleted_at IS NULL');
            $stmt->execute([(int) ($_POST['id_venta'] ?? 0)]);
            $message = $stmt->rowCount() > 0 ? 'Venta eliminada correctamente.' : 'La venta no existe o ya fue eliminada.';
        }
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); $error = $exception->getMessage(); }
}
$clients = $db->query("SELECT id_cliente,nombre,apellido FROM clientes WHERE estado='activo' ORDER BY nombre,apellido")->fetchAll(PDO::FETCH_ASSOC);
$products = $db->query('SELECT PRO_codigo,PRO_nombre_producto,PRO_precio_unitario FROM productos WHERE deleted_at IS NULL ORDER BY PRO_nombre_producto')->fetchAll(PDO::FETCH_ASSOC);

// --- Filtro por rango de fechas (desde/hasta) ---
$fechaDesde = trim($_GET['desde'] ?? '');
$fechaHasta = trim($_GET['hasta'] ?? '');
$condicionesFecha = ['v.deleted_at IS NULL'];
$parametrosFecha = [];
if ($fechaDesde !== '') { $condicionesFecha[] = 'v.fecha_venta >= ?'; $parametrosFecha[] = $fechaDesde . ' 00:00:00'; }
if ($fechaHasta !== '') { $condicionesFecha[] = 'v.fecha_venta <= ?'; $parametrosFecha[] = $fechaHasta . ' 23:59:59'; }
$whereFecha = 'WHERE ' . implode(' AND ', $condicionesFecha);

$sqlVentas = "SELECT v.id_venta,v.id_cliente,v.fecha_venta,v.total,v.estado,CONCAT(c.nombre,' ',c.apellido) AS cliente, d.codigo_producto,d.cantidad,d.precio_unitario FROM ventas v LEFT JOIN clientes c ON c.id_cliente=v.id_cliente LEFT JOIN venta_detalle d ON d.id_venta=v.id_venta $whereFecha ORDER BY v.fecha_venta DESC";
$stmtVentas = $db->prepare($sqlVentas);
$stmtVentas->execute($parametrosFecha);
$sales = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);
$totalSales = array_sum(array_map(fn($sale) => $sale['estado'] === 'Cancelada' ? 0 : (float) $sale['total'], $sales));

// --- Exportación CSV (respeta el mismo filtro de fechas aplicado en pantalla) ---
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="reporte_ventas_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 para tildes/ñ en Excel
    fputcsv($out, ['ID Venta', 'Comprador', 'Producto', 'Cantidad', 'Precio unitario', 'Fecha', 'Total', 'Estado']);
    foreach ($sales as $sale) {
        fputcsv($out, [
            $sale['id_venta'],
            $sale['cliente'] ?? 'Sin cliente',
            $sale['codigo_producto'] ?? 'Sin detalle',
            $sale['cantidad'] ?? 0,
            $sale['precio_unitario'] ?? 0,
            $sale['fecha_venta'],
            $sale['total'],
            $sale['estado'],
        ]);
    }
    fclose($out);
    exit();
}

// --- Exportación PDF (encabezado, tabla y numeración de página) ---
if (($_GET['export'] ?? '') === 'pdf') {
    require_once __DIR__ . '/../vendor/fpdf/fpdf.php';

    // FPDF (core) trabaja en Latin-1 (ISO-8859-1), no en UTF-8.
    // Antes se usaba utf8_decode() para esa misma conversion, pero esta
    // deprecada desde PHP 8.2 (genera E_DEPRECATED aunque siga funcionando).
    // mb_convert_encoding hace lo mismo sin el warning.
    function pdf_txt(string $texto): string
    {
        return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
    }

    class ReportePDF extends FPDF
    {
        public string $subtitulo = '';

        function Header()
        {
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 8, pdf_txt('C&M Soluciones Abrasivas SAS'), 0, 1, 'C');
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 6, pdf_txt('Reporte de ventas'), 0, 1, 'C');
            if ($this->subtitulo !== '') {
                $this->Cell(0, 6, pdf_txt($this->subtitulo), 0, 1, 'C');
            }
            $this->Ln(4);
        }

        function Footer()
        {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . ' de {nb}', 0, 0, 'C');
        }
    }

    $rangoTexto = 'Generado el ' . date('d/m/Y H:i');
    if ($fechaDesde !== '' || $fechaHasta !== '') {
        $rangoTexto .= ' - Periodo: ' . ($fechaDesde ?: 'inicio') . ' a ' . ($fechaHasta ?: 'hoy');
    }

    $pdf = new ReportePDF();
    $pdf->subtitulo = $rangoTexto;
    $pdf->AliasNbPages();
    $pdf->AddPage('L');
    $pdf->SetFont('Arial', 'B', 9);

    $columnas = ['ID' => 12, 'Comprador' => 40, 'Producto' => 20, 'Cant.' => 15, 'P. Unit.' => 22, 'Fecha' => 32, 'Total' => 25, 'Estado' => 25];
    foreach ($columnas as $titulo => $ancho) {
        $pdf->Cell($ancho, 8, pdf_txt($titulo), 1, 0, 'C');
    }
    $pdf->Ln();

    $pdf->SetFont('Arial', '', 8);
    foreach ($sales as $sale) {
        $pdf->Cell(12, 7, (string) $sale['id_venta'], 1);
        $pdf->Cell(40, 7, pdf_txt(mb_substr($sale['cliente'] ?? 'Sin cliente', 0, 28)), 1);
        $pdf->Cell(20, 7, (string) ($sale['codigo_producto'] ?? '-'), 1, 0, 'C');
        $pdf->Cell(15, 7, (string) ($sale['cantidad'] ?? 0), 1, 0, 'C');
        $pdf->Cell(22, 7, '$ ' . number_format((float) ($sale['precio_unitario'] ?? 0), 0, ',', '.'), 1, 0, 'R');
        $pdf->Cell(32, 7, (string) $sale['fecha_venta'], 1, 0, 'C');
        $pdf->Cell(25, 7, '$ ' . number_format((float) $sale['total'], 0, ',', '.'), 1, 0, 'R');
        $pdf->Cell(25, 7, pdf_txt($sale['estado']), 1, 0, 'C');
        $pdf->Ln();
    }

    if (!$sales) {
        $pdf->Cell(191, 8, pdf_txt('No hay ventas en el periodo seleccionado.'), 1, 1, 'C');
    }

    $pdf->Ln(4);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 8, pdf_txt('Total vendido: $ ' . number_format($totalSales, 0, ',', '.')), 0, 1, 'R');

    $pdf->Output('D', 'reporte_ventas_' . date('Y-m-d') . '.pdf');
    exit();
}
} // fin legacy
// Defaults MVC.
$clients = $clients ?? [];
$products = $products ?? [];
$sales = $sales ?? [];
$totalSales = $totalSales ?? 0;
$fechaDesde = $fechaDesde ?? trim($_GET['desde'] ?? '');
$fechaHasta = $fechaHasta ?? trim($_GET['hasta'] ?? '');
$canEdit = $canEdit ?? true;
$message = $message ?? '';
$error = $error ?? '';
// Búsqueda de texto + paginación (las calcula ReporteController::datos).
$busquedaVentas = $busquedaVentas ?? trim($_GET['q'] ?? '');
$paginaActual = $paginaActual ?? 1;
$totalPaginas = $totalPaginas ?? 1;
$totalVentas = $totalVentas ?? count($sales ?? []);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reportes y ventas | C&M</title><link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet"><link href="css/styles.css" rel="stylesheet"><link href="css/admin-dark.css" rel="stylesheet"><script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js"></script></head><body class="sb-nav-fixed"><nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark"><a class="navbar-brand ps-3" href="index.php?action=gerente">C&M ABRASIVAS</a><a class="nav-link text-white ms-auto me-4" href="index.php?action=logout"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></nav><div id="layoutSidenav"><div id="layoutSidenav_nav"><nav class="sb-sidenav sb-sidenav-dark"><div class="sb-sidenav-menu"><div class="nav"><div class="sb-sidenav-menu-heading">Menú principal</div><a class="nav-link" href="index.php?action=gerente"><div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>Dashboard</a><a class="nav-link" href="index.php?action=inventario"><div class="sb-nav-link-icon"><i class="fas fa-boxes"></i></div>Inventario</a><a class="nav-link active" href="index.php?action=reportes"><div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>Reportes</a></div></div><div class="sb-sidenav-footer">C&M Soluciones Abrasivas</div></nav></div><div id="layoutSidenav_content"><main><div class="container-fluid px-4"><div class="d-flex align-items-center justify-content-between mt-4 mb-3"><div><h1>Reportes y ventas</h1><p class="text-muted">Registra lo que compraron y el precio para actualizar las gráficas.</p></div><div class="d-flex gap-2"><a class="btn btn-outline-success" href="?action=reportes&export=csv&desde=<?php echo urlencode($fechaDesde);?>&hasta=<?php echo urlencode($fechaHasta);?>&q=<?php echo urlencode($busquedaVentas);?>"><i class="fas fa-file-csv me-1"></i>Exportar CSV</a><a class="btn btn-outline-danger" href="?action=reportes&export=pdf&desde=<?php echo urlencode($fechaDesde);?>&hasta=<?php echo urlencode($fechaHasta);?>&q=<?php echo urlencode($busquedaVentas);?>"><i class="fas fa-file-pdf me-1"></i>Exportar PDF</a><?php if($canEdit):?><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#saleModal" onclick="newSale()"><i class="fas fa-plus me-1"></i>Registrar venta</button><?php endif;?></div></div>
<form method="get" action="index.php" class="row g-2 mb-3 align-items-end"><input type="hidden" name="action" value="reportes"><div class="col-auto"><label class="form-label mb-0">Desde</label><input type="date" class="form-control" name="desde" value="<?php echo htmlspecialchars($fechaDesde,ENT_QUOTES,'UTF-8');?>"></div><div class="col-auto"><label class="form-label mb-0">Hasta</label><input type="date" class="form-control" name="hasta" value="<?php echo htmlspecialchars($fechaHasta,ENT_QUOTES,'UTF-8');?>"></div><div class="col-md-4"><label class="form-label mb-0">Buscar</label><div class="input-group"><input type="search" class="form-control" name="q" placeholder="Cliente, producto o estado..." value="<?php echo htmlspecialchars($busquedaVentas,ENT_QUOTES,'UTF-8');?>"><button type="submit" class="btn btn-outline-primary" title="Buscar"><i class="fas fa-search"></i></button></div></div><div class="col-auto"><button type="submit" class="btn btn-outline-primary"><i class="fas fa-filter me-1"></i>Filtrar</button></div><?php if($fechaDesde||$fechaHasta||$busquedaVentas):?><div class="col-auto"><a href="?action=reportes" class="btn btn-outline-secondary">Limpiar filtro</a></div><?php endif;?></form><?php if($message):?><div class="alert alert-success"><?php echo htmlspecialchars($message,ENT_QUOTES,'UTF-8');?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?php echo htmlspecialchars($error,ENT_QUOTES,'UTF-8');?></div><?php endif;?><div class="row"><div class="col-xl-3 col-md-6"><div class="card bg-primary text-white mb-4"><div class="card-body"><div class="small text-white-50">Ventas registradas</div><div class="fs-4 fw-bold"><?php echo count($sales);?></div></div></div></div><div class="col-xl-3 col-md-6"><div class="card bg-success text-white mb-4"><div class="card-body"><div class="small text-white-50">Total vendido</div><div class="fs-4 fw-bold">$ <?php echo number_format($totalSales,0,',','.');?></div></div></div></div></div><div class="card mb-4"><div class="card-header"><i class="fas fa-shopping-cart me-1"></i>Ventas y productos comprados</div><div class="card-body table-responsive"><table class="table table-striped align-middle"><thead><tr><th>ID</th><th>Comprador</th><th>Producto</th><th>Cantidad</th><th>Precio unitario</th><th>Fecha</th><th>Total</th><th>Estado</th><?php if($canEdit):?><th>Acciones</th><?php endif;?></tr></thead><tbody><?php foreach($sales as $sale):?><tr><td><?php echo (int)$sale['id_venta'];?></td><td><?php echo htmlspecialchars($sale['cliente']??'Sin cliente',ENT_QUOTES,'UTF-8');?></td><td><?php echo htmlspecialchars($sale['producto_nombre']??('PRO-'.($sale['codigo_producto']??'Sin detalle')),ENT_QUOTES,'UTF-8');?><br><small class="text-muted">#<?php echo htmlspecialchars($sale['codigo_producto']??'-',ENT_QUOTES,'UTF-8');?></small></td><td><?php echo (int)($sale['cantidad']??0);?></td><td>$ <?php echo number_format((float)($sale['precio_unitario']??0),0,',','.');?></td><td><?php echo htmlspecialchars($sale['fecha_venta'],ENT_QUOTES,'UTF-8');?></td><td>$ <?php echo number_format((float)$sale['total'],0,',','.');?></td><td><?php echo htmlspecialchars($sale['estado'],ENT_QUOTES,'UTF-8');?></td><?php if($canEdit):?><td><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#saleModal" onclick='editSale(<?php echo json_encode($sale,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);?>)'><i class="fas fa-edit"></i></button><form method="post" class="d-inline" onsubmit="return cmConfirmDelete(event,'¿Eliminar esta venta?')"><?php echo csrf_field();?><input type="hidden" name="sale_action" value="delete"><input type="hidden" name="id_venta" value="<?php echo (int)$sale['id_venta'];?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form></td><?php endif;?></tr><?php endforeach;?><?php if(!$sales):?><tr><td colspan="9" class="text-center text-muted">No hay ventas. Registra la primera para ver datos en la gráfica.</td></tr><?php endif;?></tbody></table><?php echo pager_html((int) $paginaActual, (int) $totalPaginas, ['action' => 'reportes', 'desde' => $fechaDesde, 'hasta' => $fechaHasta, 'q' => $busquedaVentas]); ?><?php if ($totalVentas > 0): ?><p class="text-muted small text-center">Mostrando <?php echo count($sales); ?> de <?php echo (int) $totalVentas; ?> ventas · página <?php echo (int) $paginaActual; ?> de <?php echo (int) $totalPaginas; ?></p><?php endif; ?></div></div></div></main></div></div><?php if($canEdit):?><div class="modal fade" id="saleModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><form method="post"><?php echo csrf_field();?><div class="modal-header"><h5 class="modal-title" id="saleTitle">Registrar venta</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="sale_action" id="saleAction" value="create"><input type="hidden" name="id_venta" id="saleId"><div class="row g-3"><div class="col-md-6"><label class="form-label">Cliente existente</label><select class="form-select" name="id_cliente" id="saleClient"><option value="">Escribe uno nuevo abajo</option><?php foreach($clients as $client):?><option value="<?php echo (int)$client['id_cliente'];?>"><?php echo htmlspecialchars($client['nombre'].' '.$client['apellido'],ENT_QUOTES,'UTF-8');?></option><?php endforeach;?></select></div><div class="col-md-6"><label class="form-label">Cliente nuevo</label><input class="form-control" name="cliente_nuevo" placeholder="Nombre y apellido"></div><div class="col-md-6"><label class="form-label">Producto comprado *</label><select class="form-select" name="codigo_producto" id="saleProduct" required><option value="">Selecciona un producto</option><?php foreach($products as $product):?><option value="<?php echo (int)$product['PRO_codigo'];?>" data-price="<?php echo htmlspecialchars($product['PRO_precio_unitario'],ENT_QUOTES,'UTF-8');?>"><?php echo htmlspecialchars($product['PRO_nombre_producto'],ENT_QUOTES,'UTF-8');?></option><?php endforeach;?></select></div><div class="col-md-2"><label class="form-label">Cantidad *</label><input class="form-control" type="number" min="1" name="cantidad" id="saleQuantity" value="1" required></div><div class="col-md-4"><label class="form-label">Precio unitario *</label><input class="form-control" type="number" min="0" step="0.01" name="precio_unitario" id="saleUnitPrice" required></div><div class="col-md-6"><label class="form-label">Fecha *</label><input class="form-control" type="datetime-local" name="fecha_venta" id="saleDate" required></div><div class="col-md-6"><label class="form-label">Total *</label><input class="form-control" type="number" min="0" step="0.01" name="total" id="saleTotal" required></div><div class="col-md-6"><label class="form-label">Estado *</label><select class="form-select" name="estado" id="saleStatus"><option>Pendiente</option><option>Pagada</option><option>Cancelada</option></select></div></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar venta</button></div></form></div></div></div><?php endif;?><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script><script>const product=document.getElementById('saleProduct'),quantity=document.getElementById('saleQuantity'),unit=document.getElementById('saleUnitPrice'),total=document.getElementById('saleTotal');function calculateTotal(){if(product&&product.selectedOptions[0]){if(!unit.value)unit.value=product.selectedOptions[0].dataset.price||0;total.value=(Number(quantity.value||0)*Number(unit.value||0)).toFixed(2);}}product?.addEventListener('change',()=>{unit.value=product.selectedOptions[0].dataset.price||0;calculateTotal();});quantity?.addEventListener('input',calculateTotal);unit?.addEventListener('input',calculateTotal);function newSale(){document.querySelector('#saleModal form').reset();document.getElementById('saleTitle').textContent='Registrar venta';document.getElementById('saleAction').value='create';document.getElementById('saleId').value='';document.getElementById('saleQuantity').value=1;}function editSale(s){document.getElementById('saleTitle').textContent='Editar venta';document.getElementById('saleAction').value='update';document.getElementById('saleId').value=s.id_venta;document.getElementById('saleClient').value=s.id_cliente;document.getElementById('saleProduct').value=s.codigo_producto||'';document.getElementById('saleQuantity').value=s.cantidad||1;document.getElementById('saleUnitPrice').value=s.precio_unitario||0;document.getElementById('saleDate').value=s.fecha_venta.replace(' ','T').slice(0,16);document.getElementById('saleTotal').value=s.total;document.getElementById('saleStatus').value=s.estado;}</script><?php require __DIR__.'/partials/swal.php'; ?></body></html>
