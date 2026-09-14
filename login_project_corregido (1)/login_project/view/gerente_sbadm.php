<?php
require_once __DIR__ . '/../config/require_auth.php';
require_role(['gerente','inventario']);

require_once __DIR__ . '/../config/conexion.php';

$db = (new Conexion())->conn;
$user = $_SESSION['user'] ?? [];
$username = $user['username'] ?? 'Gerente';
$role = $_SESSION['rol'] ?? 'gerente';
$stats = ['todaySales' => 0, 'weekSales' => 0, 'monthSales' => 0, 'stockUnits' => 0, 'pending' => 0, 'products' => 0, 'low' => 0];
$weekly = array_fill(0, 7, 0);
$monthly = array_fill(0, 6, 0);
$recentSales = [];
$paymentProofs = [];
$dashboardError = '';

try {
    $db->exec("CREATE TABLE IF NOT EXISTS comprobantes_pago (
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
    $db->exec("ALTER TABLE comprobantes_pago ADD COLUMN IF NOT EXISTS direccion_envio VARCHAR(200) NOT NULL DEFAULT '' AFTER referencia");
    $db->exec("ALTER TABLE comprobantes_pago ADD COLUMN IF NOT EXISTS numero_factura VARCHAR(50) NOT NULL DEFAULT '' AFTER direccion_envio");
    $paymentProofs = $db->query("SELECT cp.id, cp.medio_pago, cp.monto, cp.referencia, cp.direccion_envio, cp.numero_factura, cp.comprobante, cp.estado, cp.fecha_envio, COALESCE(CONCAT(u.nombre, ' ', u.apellido), u.username, 'Cliente') AS cliente FROM comprobantes_pago cp LEFT JOIN usuarios u ON u.id = cp.id_usuario ORDER BY cp.fecha_envio DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
    $productStats = $db->query('SELECT COUNT(*) AS products, COALESCE(SUM(PRO_stock_actual), 0) AS units, COALESCE(SUM(PRO_stock_actual <= PRO_stock_minimo), 0) AS low FROM productos WHERE deleted_at IS NULL')->fetch(PDO::FETCH_ASSOC);
    $stats['products'] = (int) ($productStats['products'] ?? 0);
    $stats['stockUnits'] = (int) ($productStats['units'] ?? 0);
    $stats['low'] = (int) ($productStats['low'] ?? 0);
    $hasSales = (bool) $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'ventas'")->fetchColumn();
    if ($hasSales) {
        $summary = $db->query("SELECT COALESCE(SUM(CASE WHEN DATE(fecha_venta)=CURRENT_DATE() AND estado <> 'Cancelada' THEN total ELSE 0 END),0) AS today_sales, COALESCE(SUM(CASE WHEN YEARWEEK(fecha_venta, 1)=YEARWEEK(CURRENT_DATE(), 1) AND estado <> 'Cancelada' THEN total ELSE 0 END),0) AS week_sales, COALESCE(SUM(CASE WHEN MONTH(fecha_venta)=MONTH(CURRENT_DATE()) AND YEAR(fecha_venta)=YEAR(CURRENT_DATE()) AND estado <> 'Cancelada' THEN total ELSE 0 END),0) AS month_sales, COALESCE(SUM(estado='Pendiente'),0) AS pending FROM ventas WHERE deleted_at IS NULL")->fetch(PDO::FETCH_ASSOC);
        $stats['todaySales'] = (float) ($summary['today_sales'] ?? 0);
        $stats['weekSales'] = (float) ($summary['week_sales'] ?? 0);
        $stats['monthSales'] = (float) ($summary['month_sales'] ?? 0);
        $stats['pending'] = (int) ($summary['pending'] ?? 0);
        $stmt = $db->query("SELECT WEEKDAY(fecha_venta) AS day_index, SUM(total) AS amount FROM ventas WHERE fecha_venta >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 DAY) AND estado <> 'Cancelada' AND deleted_at IS NULL GROUP BY WEEKDAY(fecha_venta)");
        foreach ($stmt as $row) { $index = (int) $row['day_index']; if ($index >= 0 && $index < 7) $weekly[$index] = (float) $row['amount']; }
        $stmt = $db->query("SELECT PERIOD_DIFF(EXTRACT(YEAR_MONTH FROM CURRENT_DATE()), EXTRACT(YEAR_MONTH FROM fecha_venta)) AS month_index, SUM(total) AS amount FROM ventas WHERE fecha_venta >= DATE_SUB(CURRENT_DATE(), INTERVAL 5 MONTH) AND estado <> 'Cancelada' AND deleted_at IS NULL GROUP BY month_index");
        foreach ($stmt as $row) { $index = (int) $row['month_index']; if ($index >= 0 && $index < 6) $monthly[5 - $index] = (float) $row['amount']; }
        $recentSales = $db->query("SELECT v.id_venta, v.fecha_venta, v.total, v.estado, CONCAT(c.nombre, ' ', c.apellido) AS cliente FROM ventas v LEFT JOIN clientes c ON c.id_cliente=v.id_cliente WHERE v.deleted_at IS NULL ORDER BY v.fecha_venta DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $exception) {
    $dashboardError = 'No fue posible cargar los indicadores. Verifica la conexión y la estructura de la base de datos.';
}

$monthLabels = [];
$monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
for ($i = 5; $i >= 0; $i--) $monthLabels[] = $monthNames[(int) date('n', strtotime("-$i months")) - 1];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistema de gestión C&M Soluciones Abrasivas">
    <title>C&M SOLUCIONES ABRASIVAS</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <link href="css/admin-dark.css" rel="stylesheet">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        :root { --dashboard-ink: #172033; --dashboard-muted: #718096; }
        body { background: #f3f6fa; }
        #layoutSidenav_content main { background: radial-gradient(circle at 92% 0%, rgba(255, 193, 7, .11), transparent 27rem), #f3f6fa; min-height: calc(100vh - 56px); }
        .dashboard-title { color: #ffc107 !important; font-weight: 800; letter-spacing: -.02em; }
        .dashboard-subtitle { color: var(--dashboard-muted); }
        #comprobantes-pago table,
        #comprobantes-pago table > :not(caption) > * > *,
        #comprobantes-pago tbody td { color: #ffffff !important; }
        #comprobantes-pago tbody td small,
        #comprobantes-pago .text-muted { color: #b8c7d1 !important; }
        .kpi-card { border: 0; border-radius: 14px; color: #fff; overflow: hidden; box-shadow: 0 12px 25px rgba(25, 39, 61, .12); transition: transform .2s ease, box-shadow .2s ease; }
        .kpi-card:hover { transform: translateY(-4px); box-shadow: 0 18px 30px rgba(25, 39, 61, .2); }
        .kpi-card .card-body { min-height: 132px; position: relative; padding: 1.35rem 1.4rem; }
        .kpi-label { font-size: .76rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; opacity: .82; }
        .kpi-value { font-size: 1.65rem; font-weight: 800; margin-top: .6rem; position: relative; z-index: 1; }
        .kpi-icon { bottom: 1.2rem; font-size: 2.35rem; opacity: .22; position: absolute; right: 1.3rem; }
        .kpi-card .card-footer { background: rgba(0, 0, 0, .12); border: 0; font-size: .78rem; padding: .75rem 1.4rem; }
        .kpi-blue { background: linear-gradient(135deg, #1769e0, #4b9bff); }
        .kpi-cyan { background: linear-gradient(135deg, #008b91, #24c7b7); }
        .kpi-gold { background: linear-gradient(135deg, #e29a00, #ffc928); }
        .kpi-green { background: linear-gradient(135deg, #11834f, #37b978); }
        .chart-card { border: 0; border-radius: 14px; box-shadow: 0 8px 22px rgba(25, 39, 61, .08); }
        .chart-card .card-header { align-items: center; background: #fff; border-bottom: 1px solid #edf0f5; color: var(--dashboard-ink); display: flex; font-size: .8rem; font-weight: 800; justify-content: space-between; letter-spacing: .03em; padding: 1rem 1.25rem; }
        .chart-status { color: #7c8798; font-size: .68rem; font-weight: 600; letter-spacing: 0; text-transform: none; }
        .chart-status i { color: #18a76b; font-size: .5rem; margin-right: .25rem; }
        .chart-wrap { height: 275px; position: relative; }
        @media (max-width: 576px) { .chart-wrap { height: 230px; } .chart-card .card-header { align-items: flex-start; flex-direction: column; gap: .35rem; } }
    </style>
</head>
<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="index.php?action=gerente">C&M ABRASIVAS</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button"><i class="fas fa-bars"></i></button>
        <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0"><div class="input-group"><input class="form-control" type="search" placeholder="Buscar productos, clientes..." aria-label="Buscar"><button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button></div></form>
        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4"><li class="nav-item dropdown"><a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown"><i class="fas fa-user fa-fw"></i> <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></a><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="index.php?action=usuario&section=perfil">Mi perfil</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item" href="index.php?action=logout">Cerrar sesión</a></li></ul></li></ul>
    </nav>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav"><nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion"><div class="sb-sidenav-menu"><div class="nav">
            <div class="sb-sidenav-menu-heading">Menú principal</div>
            <a class="nav-link active" href="index.php?action=gerente"><div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>Dashboard</a>
            <div class="sb-sidenav-menu-heading">Gestión</div>
            <a class="nav-link" href="index.php?action=inventario"><div class="sb-nav-link-icon"><i class="fas fa-boxes"></i></div>Inventario</a>
            <a class="nav-link" href="index.php?action=reportes"><div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>Reportes</a>
            <a class="nav-link" href="index.php?action=pqrs"><div class="sb-nav-link-icon"><i class="fas fa-headset"></i></div>PQRS</a>
            <?php if ($role !== 'inventario'): ?><a class="nav-link" href="index.php?action=usuario&section=usuarios"><div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>Usuarios</a><?php endif; ?>
            <div class="sb-sidenav-menu-heading">Mi cuenta</div><a class="nav-link" href="index.php?action=usuario&section=perfil"><div class="sb-nav-link-icon"><i class="fas fa-user-circle"></i></div>Mi perfil</a>
        </div></div><div class="sb-sidenav-footer"><div class="small">Inició sesión como:</div><?php echo htmlspecialchars(ucfirst($role), ENT_QUOTES, 'UTF-8'); ?></div></nav></div>
        <div id="layoutSidenav_content"><main><div class="container-fluid px-4">
            <h1 class="dashboard-title mt-4">¡Hola Gerente de C&amp;M, bienvenido!</h1>
            <ol class="breadcrumb mb-4"><li class="breadcrumb-item active dashboard-subtitle">Resumen general de C&amp;M Soluciones Abrasivas SAS</li></ol>
            <div class="card border-0 shadow-sm mb-4 bg-dark text-white"><div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3"><div><span class="text-warning text-uppercase small fw-bold"><i class="fas fa-bolt me-1"></i> Gestión rápida</span><h4 class="mt-1 mb-1">Publica productos para tus clientes</h4><p class="mb-0 text-white-50">Cada producto agregado al inventario se muestra automáticamente en el catálogo del portal cliente.</p></div><a class="btn btn-warning fw-bold text-dark" href="index.php?action=inventario"><i class="fas fa-plus me-2"></i>Agregar producto</a></div></div>
            <?php if ($dashboardError): ?><div class="alert alert-warning" role="alert"><i class="fas fa-triangle-exclamation me-2"></i><?php echo htmlspecialchars($dashboardError, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <div class="card border-0 shadow-sm mb-4" id="comprobantes-pago"><div class="card-header d-flex align-items-center justify-content-between"><span><i class="fas fa-receipt me-2"></i>COMPROBANTES DE PAGO RECIBIDOS</span><span class="badge bg-warning text-dark"><?php echo count(array_filter($paymentProofs, static fn ($proof) => $proof['estado'] === 'Pendiente')); ?> pendientes</span></div><div class="card-body table-responsive"><table class="table table-striped table-bordered align-middle"><thead><tr><th>Factura</th><th>Cliente</th><th>Medio</th><th>Monto</th><th>Referencia</th><th>Dirección de envío</th><th>Comprobante</th><th>Estado</th><th>Revisión</th></tr></thead><tbody><?php if (!$paymentProofs): ?><tr><td colspan="9" class="text-center text-muted">No hay comprobantes enviados.</td></tr><?php endif; ?><?php foreach ($paymentProofs as $proof): ?><tr><td><?php echo htmlspecialchars($proof['numero_factura'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($proof['cliente'], ENT_QUOTES, 'UTF-8'); ?><br><small><?php echo date('d/m/Y H:i', strtotime($proof['fecha_envio'])); ?></small></td><td><?php echo htmlspecialchars($proof['medio_pago'], ENT_QUOTES, 'UTF-8'); ?></td><td>$ <?php echo number_format((float) $proof['monto'], 0, ',', '.'); ?></td><td><?php echo htmlspecialchars($proof['referencia'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($proof['direccion_envio'], ENT_QUOTES, 'UTF-8'); ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($proof['comprobante'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><i class="fas fa-eye me-1"></i>Ver prueba</a></td><td><span class="badge <?php echo $proof['estado'] === 'Aprobado' ? 'bg-success' : ($proof['estado'] === 'Rechazado' ? 'bg-danger' : 'bg-warning text-dark'); ?>"><?php echo htmlspecialchars($proof['estado'], ENT_QUOTES, 'UTF-8'); ?></span></td><td><?php if ($proof['estado'] === 'Pendiente'): ?><form method="post" action="index.php?action=gerente#comprobantes-pago" class="d-flex gap-1"><input type="hidden" name="action" value="actualizar_comprobante_pago"><?php echo csrf_field(); ?><input type="hidden" name="comprobante_id" value="<?php echo (int) $proof['id']; ?>"><button class="btn btn-sm btn-success" name="estado" value="Aprobado" type="submit">Aprobar</button><button class="btn btn-sm btn-danger" name="estado" value="Rechazado" type="submit">Rechazar</button></form><?php else: ?><span class="text-muted">Revisado</span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></div>
            <div class="row">
                <div class="col-xl-3 col-md-6"><a class="text-decoration-none" href="index.php?action=reportes"><div class="card kpi-card kpi-blue mb-4"><div class="card-body"><div class="kpi-label">Ventas del día</div><div id="todaySalesMetric" class="kpi-value">$ <?php echo number_format($stats['todaySales'], 0, ',', '.'); ?></div><i class="fas fa-sun kpi-icon"></i></div><div class="card-footer">Hoy <i class="fas fa-arrow-up ms-1"></i></div></div></a></div>
                <div class="col-xl-3 col-md-6"><a class="text-decoration-none" href="index.php?action=reportes"><div class="card kpi-card kpi-cyan mb-4"><div class="card-body"><div class="kpi-label">Ventas de la semana</div><div id="weekSalesMetric" class="kpi-value">$ <?php echo number_format($stats['weekSales'], 0, ',', '.'); ?></div><i class="fas fa-chart-line kpi-icon"></i></div><div class="card-footer">Lunes a domingo <i class="fas fa-arrow-up ms-1"></i></div></div></a></div>
                <div class="col-xl-3 col-md-6"><a class="text-decoration-none" href="index.php?action=reportes"><div class="card kpi-card kpi-gold mb-4"><div class="card-body"><div class="kpi-label">Ventas del mes</div><div id="monthSalesMetric" class="kpi-value">$ <?php echo number_format($stats['monthSales'], 0, ',', '.'); ?></div><i class="fas fa-coins kpi-icon"></i></div><div class="card-footer">Mes actual <i class="fas fa-arrow-up ms-1"></i></div></div></a></div>
                <div class="col-xl-3 col-md-6"><a class="text-decoration-none" href="index.php?action=inventario"><div class="card kpi-card kpi-green mb-4"><div class="card-body"><div class="kpi-label">Productos en stock</div><div id="stockMetric" class="kpi-value"><?php echo number_format($stats['stockUnits'], 0, ',', '.'); ?></div><i class="fas fa-boxes-stacked kpi-icon"></i></div><div class="card-footer">Unidades disponibles <i class="fas fa-arrow-right ms-1"></i></div></div></a></div>
            </div>
            <div class="row"><div class="col-xl-7"><div class="card chart-card mb-4"><div class="card-header"><span><i class="fas fa-chart-line me-2"></i>VENTAS DE LA SEMANA</span><span class="chart-status"><i class="fas fa-circle"></i> Actualización automática</span></div><div class="card-body"><div class="chart-wrap"><canvas id="myAreaChart"></canvas></div></div></div></div><div class="col-xl-5"><div class="card chart-card mb-4"><div class="card-header"><span><i class="fas fa-chart-column me-2"></i>VENTAS DEL MES</span><span class="chart-status">Últimos 6 meses</span></div><div class="card-body"><div class="chart-wrap"><canvas id="myBarChart"></canvas></div></div></div></div></div>
            <div class="card mb-4"><div class="card-header d-flex align-items-center justify-content-between"><span><i class="fas fa-table me-1"></i> VENTAS RECIENTES</span><a class="btn btn-sm btn-primary" href="index.php?action=reportes"><i class="fas fa-plus me-1"></i>Registrar venta</a></div><div class="card-body table-responsive"><table class="table table-striped table-bordered"><thead><tr><th>ID Venta</th><th>Fecha</th><th>Cliente</th><th>Total</th><th>Estado</th></tr></thead><tbody><?php foreach ($recentSales as $sale): ?><tr><td><?php echo (int) $sale['id_venta']; ?></td><td><?php echo htmlspecialchars($sale['fecha_venta'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($sale['cliente'] ?? 'Sin cliente', ENT_QUOTES, 'UTF-8'); ?></td><td>$ <?php echo number_format((float) $sale['total'], 0, ',', '.'); ?></td><td><?php echo htmlspecialchars($sale['estado'], ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endforeach; ?><?php if (!$recentSales): ?><tr><td colspan="5" class="text-center text-muted">No hay ventas registradas. Usa “Registrar venta” para alimentar las gráficas.</td></tr><?php endif; ?></tbody></table></div></div>
        </div></main><footer class="py-4 bg-light mt-auto"><div class="container-fluid px-4"><div class="small text-muted">C&M Soluciones Abrasivas SAS 2026</div></div></footer></div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js"></script><script src="js/scripts.js"></script>
    <script>const money={callback:value=>'$ '+Number(value).toLocaleString('es-CO')};const chartOptions={maintainAspectRatio:false,animation:{duration:1200,easing:'easeOutQuart'},legend:{display:false},scales:{yAxes:[{ticks:{beginAtZero:true,min:0,callback:value=>'$ '+Number(value).toLocaleString('es-CO')},gridLines:{color:'rgba(0,0,0,.08)'}}],xAxes:[{gridLines:{display:false}}]},tooltips:{callbacks:{label:tooltipItem=>' $ '+Number(tooltipItem.yLabel).toLocaleString('es-CO')}}};const areaChart=new Chart(document.getElementById('myAreaChart'),{type:'line',data:{labels:['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'],datasets:[{label:'Ventas',backgroundColor:'rgba(13,110,253,.16)',borderColor:'#0d6efd',pointBackgroundColor:'#ffc107',pointBorderColor:'#fff',pointRadius:5,lineTension:.35,data:<?php echo json_encode($weekly); ?>}]},options:chartOptions});const barChart=new Chart(document.getElementById('myBarChart'),{type:'bar',data:{labels:<?php echo json_encode($monthLabels); ?>,datasets:[{label:'Ventas',backgroundColor:['#0d6efd','#11a8a0','#ffc107','#f59e0b','#e94b65','#7b61ff'],borderRadius:6,data:<?php echo json_encode($monthly); ?>}]},options:chartOptions});function formatMoney(value){return '$ '+Number(value).toLocaleString('es-CO');}function refreshDashboard(){fetch('index.php?action=dashboard_data',{headers:{Accept:'application/json'},cache:'no-store'}).then(response=>{if(response.redirected&&response.url&&response.url.indexOf('action=login')>-1){window.location.href=response.url;return Promise.reject();}return response.ok?response.json():Promise.reject();}).then(data=>{document.getElementById('todaySalesMetric').textContent=formatMoney(data.todaySales);document.getElementById('weekSalesMetric').textContent=formatMoney(data.weekSales);document.getElementById('monthSalesMetric').textContent=formatMoney(data.monthSales);document.getElementById('stockMetric').textContent=Number(data.stockUnits).toLocaleString('es-CO');areaChart.data.datasets[0].data=data.weekly;barChart.data.datasets[0].data=data.monthly;areaChart.update();barChart.update();}).catch(()=>{});}setInterval(refreshDashboard,30000);</script>
</body>
</html>
