<?php
require_once __DIR__ . '/../config/require_auth.php';
require_role(['cliente','gerente']);

require_once __DIR__ . '/../config/conexion.php';
// MVC: si ClienteController ya entregó $catalogProducts/$pqrs/etc., solo presentar.
// Bloque legacy (acceso directo) preservado para no perder funcionalidad.
if (empty($__MVC_READY ?? null)) {
$catalogProducts = [];
$pqrs = [];
$pqrsMessage = '';
$pqrsError = '';
$stockByName = [];
$favoriteProductIds = [];
$favoriteMessage = '';
$paymentMessage = trim($_GET['mensaje_pago'] ?? '');
$paymentAmount = max(0, (float) ($_GET['total_pago'] ?? 0));
$paymentInvoice = trim($_GET['factura_pago'] ?? '');
$paymentProofs = [];
try {
    $db = (new Conexion())->conn;
    $catalogProducts = $db->query("SELECT PRO_codigo, PRO_nombre_producto, PRO_descripcion, PRO_marca, PRO_imagen_url, PRO_precio_unitario, PRO_stock_actual FROM productos WHERE deleted_at IS NULL ORDER BY PRO_codigo DESC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($catalogProducts as $product) {
        $stockByName[$product['PRO_nombre_producto']] = (int) $product['PRO_stock_actual'];
    }

    $favoriteUser = trim((string) ($_SESSION['user']['documento_id'] ?? ''));
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_favorite' && $favoriteUser !== '') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $stmt = $db->prepare('SELECT COUNT(*) FROM auditoria_favoritos WHERE usuario = :usuario AND producto = :producto');
        $stmt->execute([':usuario' => $favoriteUser, ':producto' => $productId]);
        if ((int) $stmt->fetchColumn() > 0) {
            $stmt = $db->prepare('DELETE FROM auditoria_favoritos WHERE usuario = :usuario AND producto = :producto');
            $favoriteMessage = 'Producto retirado de favoritos.';
        } else {
            $stmt = $db->prepare('INSERT INTO auditoria_favoritos (usuario, producto, fecha) VALUES (:usuario, :producto, NOW())');
            $favoriteMessage = 'Producto agregado a favoritos.';
        }
        $stmt->execute([':usuario' => $favoriteUser, ':producto' => $productId]);
    }

    $stmt = $db->prepare('SELECT producto FROM auditoria_favoritos WHERE usuario = :usuario');
    $stmt->execute([':usuario' => $favoriteUser]);
    $favoriteProductIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

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
    $stmt = $db->prepare('SELECT id, medio_pago, monto, referencia, direccion_envio, numero_factura, estado, fecha_envio FROM comprobantes_pago WHERE id_usuario = ? ORDER BY fecha_envio DESC LIMIT 10');
    $stmt->execute([(int) ($_SESSION['user']['id'] ?? 0)]);
    $paymentProofs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $db->exec("CREATE TABLE IF NOT EXISTS pqrs (
        id_pqrs INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        tipo VARCHAR(80) NOT NULL,
        descripcion TEXT NOT NULL,
        estado ENUM('Pendiente', 'En revisión', 'Resuelta', 'Cancelada') NOT NULL DEFAULT 'Pendiente',
        fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_pqrs_usuario (id_usuario),
        INDEX idx_pqrs_estado (estado)
    ) ENGINE=InnoDB");

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'crear_pqrs') {
        $tipo = trim($_POST['tipo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $tiposValidos = ['Queja sobre producto', 'Reclamo por entrega', 'Sugerencia'];

        if (in_array($tipo, $tiposValidos, true) && $descripcion !== '') {
            $stmt = $db->prepare('INSERT INTO pqrs (id_usuario, tipo, descripcion) VALUES (:id_usuario, :tipo, :descripcion)');
            $stmt->execute([
                ':id_usuario' => (int) ($_SESSION['user']['id'] ?? 0),
                ':tipo' => $tipo,
                ':descripcion' => $descripcion,
            ]);
            $pqrsMessage = 'Tu PQRS fue enviada correctamente.';
        } else {
            $pqrsError = 'Selecciona un tipo y escribe la descripción de tu solicitud.';
        }
    }

    $stmt = $db->prepare('SELECT id_pqrs, tipo, descripcion, estado, fecha_creacion FROM pqrs WHERE id_usuario = :id_usuario ORDER BY fecha_creacion DESC');
    $stmt->execute([':id_usuario' => (int) ($_SESSION['user']['id'] ?? 0)]);
    $pqrs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ---- Indicadores reales del portafolio del cliente ----
    $clienteDoc = trim((string) ($_SESSION['user']['documento_id'] ?? ''));

    // Mis compras del mes: suma de facturas de los pedidos del cliente en el mes actual
    $kpiComprasMes = 0.0;
    $misFacturas = [];
    $facturasPendientes = 0;
    if ($clienteDoc !== '') {
        // Monto facturado en el mes
        $stmtCompras = $db->prepare("SELECT COALESCE(SUM(fp.FAC_total),0) AS total
            FROM factura_pedido fp
            INNER JOIN pedido p ON p.PED_id_pedido = fp.PED_id_pedido
            WHERE p.USU_documento_identidad = :doc
              AND MONTH(fp.FAC_fecha) = MONTH(CURDATE())
              AND YEAR(fp.FAC_fecha) = YEAR(CURDATE())");
        $stmtCompras->execute([':doc' => $clienteDoc]);
        $kpiComprasMes = (float) ($stmtCompras->fetchColumn() ?: 0);

        // Facturas del cliente (con estado del pedido)
        $stmtFacturas = $db->prepare("SELECT fp.FAC_id_factura, fp.FAC_numero_factura, fp.FAC_fecha, fp.FAC_tipo_pago,
                fp.FAC_direccion_envio, fp.FAC_total, p.PED_estado
            FROM factura_pedido fp
            INNER JOIN pedido p ON p.PED_id_pedido = fp.PED_id_pedido
            WHERE p.USU_documento_identidad = :doc
            ORDER BY fp.FAC_fecha DESC LIMIT 20");
        $stmtFacturas->execute([':doc' => $clienteDoc]);
        $misFacturas = $stmtFacturas->fetchAll(PDO::FETCH_ASSOC);

        // Facturas por pagar: pedidos facturados aún pendientes de pago
        $stmtPendientes = $db->prepare("SELECT COUNT(*) FROM factura_pedido fp
            INNER JOIN pedido p ON p.PED_id_pedido = fp.PED_id_pedido
            WHERE p.USU_documento_identidad = :doc AND p.PED_estado = 'pendiente'");
        $stmtPendientes->execute([':doc' => $clienteDoc]);
        $facturasPendientes = (int) ($stmtPendientes->fetchColumn() ?: 0);
    }

    // PQRS: resueltas vs total
    $mqrsTotal = count($pqrs);
    $pqrsResueltas = 0;
    foreach ($pqrs as $item) {
        if (($item['estado'] ?? '') === 'Resuelta') {
            $pqrsResueltas++;
        }
    }
} catch (Throwable $exception) {
    $catalogProducts = [];
    $pqrsError = 'No fue posible cargar tus PQRS en este momento.';
}
} // fin legacy
$kpiComprasMes = $kpiComprasMes ?? 0.0;
$misFacturas = $misFacturas ?? [];
$facturasPendientes = $facturasPendientes ?? 0;
$pqrsResueltas = $pqrsResueltas ?? 0;
$mqrsTotal = $mqrsTotal ?? 0;
// Defaults MVC (cuando viene del controlador).
$catalogProducts = $catalogProducts ?? [];
$pqrs = $pqrs ?? [];
$pqrsMessage = $pqrsMessage ?? '';
$pqrsError = $pqrsError ?? '';
$stockByName = $stockByName ?? [];
$favoriteProductIds = $favoriteProductIds ?? [];
$favoriteMessage = $favoriteMessage ?? '';
$paymentMessage = $paymentMessage ?? trim($_GET['mensaje_pago'] ?? '');
$paymentAmount = $paymentAmount ?? 0;
$paymentInvoice = $paymentInvoice ?? '';
$paymentProofs = $paymentProofs ?? [];
$busquedaCatalogo = $busquedaCatalogo ?? trim($_GET['q'] ?? '');
$paginaCatalogo = $paginaCatalogo ?? 1;
$totalPaginasCatalogo = $totalPaginasCatalogo ?? 1;
$totalCatalogo = $totalCatalogo ?? count($catalogProducts);
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="Portal de Cliente - C&M Soluciones Abrasivas" />
        <title>Portal Cliente - C&M SOLUCIONES ABRASIVAS</title>
        
        <!-- CSS -->
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    </head>
    <body class="sb-nav-fixed">
        <!-- Top Navbar -->
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
            <a class="navbar-brand ps-3" href="index.php?action=cliente">C&M CLIENTES</a>
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Navbar Search: consulta real al catálogo (GET ?action=cliente&q=) -->
            <form action="index.php#catalogo" method="get" class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
                <input type="hidden" name="action" value="cliente">
                <div class="input-group">
                    <input name="q" class="form-control" type="search" placeholder="Buscar abrasivos, discos..." aria-label="Buscar..." value="<?php echo htmlspecialchars($busquedaCatalogo, ENT_QUOTES, 'UTF-8'); ?>" />
                    <button class="btn btn-primary" type="submit" title="Buscar"><i class="fas fa-search"></i></button>
                </div>
            </form>
            
            <!-- Navbar User Menu -->
            <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user fa-fw"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="index.php?action=usuario&section=perfil">Mi Perfil</a></li>
                        <li><a class="dropdown-item" href="index.php?action=logout">Cerrar sesión</a></li>
                    </ul>
                </li>
            </ul>
        </nav>

        <div id="layoutSidenav">
            <!-- Sidebar Navigation -->
            <div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav">
                            <div class="sb-sidenav-menu-heading">Menú Principal</div>
                            <a class="nav-link active" href="index.php?action=cliente">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                Mi Panel
                            </a>
                            
                            <div class="sb-sidenav-menu-heading">Tienda y Pedidos</div>
                            <a class="nav-link" href="#catalogo">
                                <div class="sb-nav-link-icon"><i class="fas fa-store"></i></div>
                                Ver el Catálogo
                            </a>
                            <a class="nav-link" href="#carrito">
                                <div class="sb-nav-link-icon"><i class="fas fa-shopping-cart"></i></div>
                                Carrito de Compras <span class="badge bg-danger ms-2" id="cartBadge">0</span>
                            </a>
                            <a class="nav-link" href="#facturas">
                                <div class="sb-nav-link-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                                Mis Facturas
                            </a>
                            <a class="nav-link" href="#pagos">
                                <div class="sb-nav-link-icon"><i class="fas fa-credit-card"></i></div>
                                Medios de Pago
                            </a>
                            <a class="nav-link" href="#quejas">
                                <div class="sb-nav-link-icon"><i class="fas fa-headset"></i></div>
                                Quejas y PQRS
                            </a>
                        </div>
                    </div>
                    <div class="sb-sidenav-footer">
                        <div class="small">Inició sesión como:</div>
                        Clientes
                    </div>
                </nav>
            </div>

            <!-- Main Content -->
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">¡Bienvenido, Cliente C&M!</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item active">Panel de control y autogestión de compras</li>
                        </ol>

                        <!-- Dashboard Cards -->
                        <div class="row">
                            <!-- Pedidos Activos -->
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-primary text-white mb-4">
                                    <div class="card-body d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="text-white-50 small">Mis Compras del Mes</div>
                                            <div class="fs-4 fw-bold">$ <?php echo number_format($kpiComprasMes, 0, ',', '.'); ?></div>
                                        </div>
                                        <i class="fas fa-shopping-bag fa-2x opacity-75"></i>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="#facturas">Ver Facturas</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Carrito actual -->
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-warning text-white mb-4">
                                    <div class="card-body d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="text-white-50 small">Artículos en Carrito</div>
                                            <div class="fs-4 fw-bold" id="cardItemCount">0 ítems</div>
                                        </div>
                                        <i class="fas fa-shopping-cart fa-2x opacity-75"></i>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="#carrito">Ir al Carrito</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Facturas Pendientes -->
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-danger text-white mb-4">
                                    <div class="card-body d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="text-white-50 small">Facturas por Pagar</div>
                                            <div class="fs-4 fw-bold"><?php echo (int) $facturasPendientes; ?></div>
                                        </div>
                                        <i class="fas fa-file-invoice fa-2x opacity-75"></i>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="#pagos">Pagar Ahora</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Estado de Quejas -->
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-success text-white mb-4">
                                    <div class="card-body d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="text-white-50 small">PQRS Resueltas</div>
                                            <div class="fs-4 fw-bold"><?php echo (int) $pqrsResueltas . ' / ' . (int) $mqrsTotal; ?></div>
                                        </div>
                                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="#quejas">Ver PQRS</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN 1: CARRITO DE COMPRAS (Sumar y Eliminar Productos) -->
                        <div class="card mb-4" id="carrito">
                            <div class="card-header bg-dark text-white">
                                <i class="fas fa-shopping-cart me-1"></i>
                                <strong>CARRITO DE COMPRAS ACTUAL</strong>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Producto Abrasivo</th>
                                                <th>Precio Unitario</th>
                                                <th>Estado</th>
                                                <th style="width: 180px;">Cantidad</th>
                                                <th>Subtotal</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cartTableBody">
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3 border-top pt-3">
                                    <h4>Total Carrito: <span id="cartTotal" class="text-primary">$ 0</span></h4>
                                    <button type="button" class="btn btn-success btn-lg" onclick="proceedToPayment()"><i class="fas fa-lock me-2"></i> Proceder al Pago</button>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN 2: VER EL CATÁLOGO DE PRODUCTOS -->
                        <div class="card mb-4" id="catalogo">
                            <div class="card-header bg-dark text-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <span><i class="fas fa-store me-1"></i>
                                <strong>CATÁLOGO DE PRODUCTOS ABRASIVOS</strong></span>
                                <form action="index.php#catalogo" method="get" class="d-flex gap-1">
                                    <input type="hidden" name="action" value="cliente">
                                    <div class="input-group input-group-sm">
                                        <input type="search" name="q" class="form-control" placeholder="Buscar en catálogo..." value="<?php echo htmlspecialchars($busquedaCatalogo, ENT_QUOTES, 'UTF-8'); ?>">
                                        <button class="btn btn-warning" type="submit" title="Buscar"><i class="fas fa-search"></i></button>
                                    </div>
                                    <?php if ($busquedaCatalogo !== ''): ?><a href="index.php?action=cliente#catalogo" class="btn btn-sm btn-outline-light">Limpiar</a><?php endif; ?>
                                </form>
                            </div>
                            <div class="card-body">
                                <?php if ($favoriteMessage): ?><div class="alert alert-success py-2"><?php echo htmlspecialchars($favoriteMessage, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                                <div class="row">
                                    <?php foreach ($catalogProducts as $product):
                                        $productName = htmlspecialchars($product['PRO_nombre_producto'], ENT_QUOTES, 'UTF-8');
                                        $description = htmlspecialchars($product['PRO_descripcion'] ?: 'Producto abrasivo para aplicaciones industriales.', ENT_QUOTES, 'UTF-8');
                                        $brand = htmlspecialchars($product['PRO_marca'] ?: 'C&M', ENT_QUOTES, 'UTF-8');
                                        $price = (float) $product['PRO_precio_unitario'];
                                        $stock = (int) $product['PRO_stock_actual'];
                                        $image = trim((string) ($product['PRO_imagen_url'] ?? ''));
                                    ?>
                                        <div class="col-md-4 mb-3 catalog-product-card" data-product-search="<?php echo htmlspecialchars(strtolower($product['PRO_codigo'] . ' ' . $product['PRO_nombre_producto'] . ' ' . ($product['PRO_descripcion'] ?? '') . ' ' . ($product['PRO_marca'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>">
                                            <div class="card h-100 shadow-sm border-0">
                                                <?php if ($image): ?><img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo $productName; ?>" class="card-img-top" style="height:170px;object-fit:cover"><?php else: ?><div class="d-flex align-items-center justify-content-center bg-light text-warning" style="height:170px"><i class="fas fa-compact-disc fa-4x"></i></div><?php endif; ?>
                                                <div class="card-body d-flex flex-column">
                                                    <span class="small text-muted text-uppercase"><?php echo $brand; ?></span>
                                                    <h5 class="card-title mt-1"><?php echo $productName; ?></h5>
                                                    <p class="card-text text-muted small flex-grow-1"><?php echo $description; ?></p>
                                                    <div class="d-flex justify-content-between align-items-center mb-3"><strong class="text-success">$ <?php echo number_format($price, 0, ',', '.'); ?></strong><span class="badge <?php echo $stock > 0 ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $stock > 0 ? $stock . ' disponibles' : 'Agotado'; ?></span></div>
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-outline-primary btn-sm flex-grow-1" <?php echo $stock > 0 ? '' : 'disabled'; ?> onclick="addToCart(<?php echo htmlspecialchars(json_encode($product['PRO_nombre_producto']), ENT_QUOTES, 'UTF-8'); ?>, <?php echo $price; ?>, <?php echo $stock; ?>)"><i class="fas fa-cart-plus"></i> <?php echo $stock > 0 ? 'Agregar al Carrito' : 'Sin existencias'; ?></button>
                                                        <form method="POST" action="index.php?action=cliente#catalogo">
                                                            <?php echo csrf_field(); ?>
                                                            <input type="hidden" name="action" value="toggle_favorite">
                                                            <input type="hidden" name="product_id" value="<?php echo (int) $product['PRO_codigo']; ?>">
                                                            <button class="btn <?php echo in_array((int) $product['PRO_codigo'], $favoriteProductIds, true) ? 'btn-danger' : 'btn-outline-danger'; ?> btn-sm" type="submit" title="<?php echo in_array((int) $product['PRO_codigo'], $favoriteProductIds, true) ? 'Quitar de favoritos' : 'Agregar a favoritos'; ?>"><i class="fas fa-heart"></i></button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (!$catalogProducts): ?><div class="col-12"><div class="alert alert-info mb-0"><i class="fas fa-info-circle me-2"></i><?php echo $busquedaCatalogo !== '' ? 'Sin resultados para "' . htmlspecialchars($busquedaCatalogo, ENT_QUOTES, 'UTF-8') . '". Prueba con otra palabra.' : 'El catálogo está esperando nuevos productos del gerente.'; ?></div></div><?php endif; ?>
                                </div>
                                <?php echo pager_html((int) $paginaCatalogo, (int) $totalPaginasCatalogo, ['action' => 'cliente', 'q' => $busquedaCatalogo], 'page', 'catalogo'); ?>
                                <?php if ($totalCatalogo > 0): ?><p class="text-muted small text-center mb-0">Mostrando <?php echo count($catalogProducts); ?> de <?php echo (int) $totalCatalogo; ?> productos<?php if ($busquedaCatalogo !== ''): ?> para "<strong><?php echo htmlspecialchars($busquedaCatalogo, ENT_QUOTES, 'UTF-8'); ?></strong>"<?php endif; ?> · página <?php echo (int) $paginaCatalogo; ?> de <?php echo (int) $totalPaginasCatalogo; ?></p><?php endif; ?>
                            </div>
                        </div>

                        <!-- SECCIÓN 3: FACTURAS Y MEDIOS DE PAGO -->
                        <div class="row">
                            <!-- Tabla de Facturas -->
                            <div class="col-xl-6">
                                <div class="card mb-4" id="facturas">
                                    <div class="card-header bg-dark text-white">
                                        <i class="fas fa-file-invoice-dollar me-1"></i>
                                        <strong>MIS FACTURAS</strong>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>ID Factura</th>
                                                    <th>Fecha</th>
                                                    <th>Tipo de pago</th>
                                                    <th>Direccion de Envío</th>
                                                    <th>Total</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($misFacturas): ?>
                                                    <?php foreach ($misFacturas as $factura):
                                                        $tipoPago = ['1' => 'Nequi', '2' => 'Tarjeta de Débito', '3' => 'Tarjeta de Crédito', '4' => 'Bancolombia'][(string) ($factura['FAC_tipo_pago'] ?? '')] ?? 'Pago';
                                                        $estadoFactura = strtolower($factura['PED_estado'] ?? 'pendiente');
                                                        $estadoClase = in_array($estadoFactura, ['pagado', 'pagada', 'facturado', 'completado'], true) ? 'bg-success' : 'bg-danger';
                                                    ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($factura['FAC_numero_factura'] ?: ('FAC-' . $factura['FAC_id_factura'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?php echo date('d/m/Y', strtotime($factura['FAC_fecha'])); ?></td>
                                                        <td><?php echo htmlspecialchars($tipoPago, ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?php echo htmlspecialchars($factura['FAC_direccion_envio'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td>$ <?php echo number_format((float) $factura['FAC_total'], 0, ',', '.'); ?></td>
                                                        <td><span class="badge <?php echo $estadoClase; ?>"><?php echo htmlspecialchars(ucfirst($estadoFactura), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr><td colspan="6" class="text-center text-muted">No tienes facturas registradas todavía.</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Medios de Pago -->
                            <div class="col-xl-6">
                                <div class="card mb-4" id="pagos">
                                    <div class="card-header bg-dark text-white">
                                        <i class="fas fa-credit-card me-1"></i>
                                        <strong>MEDIOS DE PAGO Y COMPROBANTES</strong>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($paymentMessage !== ''): ?><div class="alert alert-info py-2"><?php echo htmlspecialchars($paymentMessage, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                                        <p class="text-muted small">Envía el comprobante para que el gerente valide tu pago.</p>
                                        <form method="POST" action="index.php?action=cliente#pagos" enctype="multipart/form-data" class="row g-2">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="enviar_comprobante_pago">
                                            <input type="hidden" name="numero_factura" value="<?php echo htmlspecialchars($paymentInvoice, ENT_QUOTES, 'UTF-8'); ?>">
                                            <div class="col-md-6"><label for="medio_pago" class="form-label">Medio de pago</label><select id="medio_pago" name="medio_pago" class="form-select" required><option value="">Selecciona una opción</option><option value="Nequi">Nequi</option><option value="Bancolombia">Bancolombia</option><option value="Davivienda">Davivienda</option></select></div>
                                            <div class="col-md-6"><label for="numero_factura_pago" class="form-label">Número de factura</label><input id="numero_factura_pago" type="text" value="<?php echo htmlspecialchars($paymentInvoice, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" readonly required></div>
                                            <div class="col-md-6"><label for="monto_pago" class="form-label">Monto del carrito</label><input id="monto_pago" name="monto" type="number" min="1" step="0.01" value="<?php echo $paymentAmount > 0 ? htmlspecialchars((string) $paymentAmount, ENT_QUOTES, 'UTF-8') : ''; ?>" class="form-control" readonly required><small class="text-muted">Se calcula con los productos agregados al carrito.</small></div>
                                            <div class="col-md-6"><label for="referencia_pago" class="form-label">Referencia</label><input id="referencia_pago" name="referencia" maxlength="100" class="form-control" placeholder="Número de transacción" required></div>
                                            <div class="col-12"><label for="direccion_envio" class="form-label">Dirección de envío</label><input id="direccion_envio" name="direccion_envio" maxlength="200" class="form-control" placeholder="Dirección donde recibirás el pedido" required></div>
                                            <div class="col-md-6"><label for="comprobante_pago" class="form-label">Comprobante (PDF, JPG o PNG)</label><input id="comprobante_pago" name="comprobante" type="file" accept="application/pdf,image/jpeg,image/png" class="form-control" required></div>
                                            <div class="col-12"><button class="btn btn-success w-100" type="submit"><i class="fas fa-paper-plane me-1"></i>Enviar comprobante al gerente</button></div>
                                        </form>
                                        <?php if ($paymentProofs): ?><hr><h6>Mis comprobantes</h6><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Factura</th><th>Medio</th><th>Monto</th><th>Referencia</th><th>Dirección</th><th>Estado</th></tr></thead><tbody><?php foreach ($paymentProofs as $proof): ?><tr><td><?php echo htmlspecialchars($proof['numero_factura'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($proof['medio_pago'], ENT_QUOTES, 'UTF-8'); ?></td><td>$ <?php echo number_format((float) $proof['monto'], 0, ',', '.'); ?></td><td><?php echo htmlspecialchars($proof['referencia'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($proof['direccion_envio'], ENT_QUOTES, 'UTF-8'); ?></td><td><span class="badge <?php echo $proof['estado'] === 'Aprobado' ? 'bg-success' : ($proof['estado'] === 'Rechazado' ? 'bg-danger' : 'bg-warning text-dark'); ?>"><?php echo htmlspecialchars($proof['estado'], ENT_QUOTES, 'UTF-8'); ?></span></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN 4: QUEJAS O PQRS -->
                        <div class="card mb-4" id="quejas">
                            <div class="card-header bg-dark text-white">
                                <i class="fas fa-headset me-1"></i>
                                <strong>CENTRO DE SOPORTE Y QUEJAS (PQRS)</strong>
                            </div>
                            <div class="card-body">
                                <?php if ($pqrsMessage): ?><div class="alert alert-success"><?php echo htmlspecialchars($pqrsMessage, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                                <?php if ($pqrsError): ?><div class="alert alert-danger"><?php echo htmlspecialchars($pqrsError, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                                <form method="POST" action="index.php?action=cliente#quejas">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="crear_pqrs">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="tipoPqrs" class="form-label">Tipo de Solicitud</label>
                                            <select id="tipoPqrs" name="tipo" class="form-select" required>
                                                <option value="">Selecciona una opción</option>
                                                <option>Queja sobre producto</option>
                                                <option>Reclamo por entrega</option>
                                                <option>Sugerencia</option>
                                            </select>
                                        </div>
                                        <div class="col-md-8 mb-3">
                                            <label for="descripcionPqrs" class="form-label">Descripción del caso</label>
                                            <textarea id="descripcionPqrs" name="descripcion" class="form-control" rows="2" placeholder="Detalle su solicitud..." required></textarea>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane"></i> Enviar PQRS</button>
                                </form>
                                <hr>
                                <h5 class="mb-3">Mis quejas y solicitudes</h5>
                                <?php if ($pqrs): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle mb-0">
                                            <thead class="table-light">
                                                <tr><th>Tipo</th><th>Descripción</th><th>Fecha</th><th>Estado</th></tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pqrs as $item):
                                                    $statusClass = match ($item['estado']) {
                                                        'Resuelta' => 'bg-success',
                                                        'Cancelada' => 'bg-danger',
                                                        'En revisión' => 'bg-warning text-dark',
                                                        default => 'bg-secondary',
                                                    };
                                                ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($item['tipo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?php echo htmlspecialchars($item['descripcion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?php echo date('d/m/Y H:i', strtotime($item['fecha_creacion'])); ?></td>
                                                        <td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($item['estado'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info mb-0">Todavía no tienes quejas o solicitudes registradas.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </main>

                <footer class="py-4 bg-light mt-auto">
                    <div class="container-fluid px-4">
                        <div class="d-flex align-items-center justify-content-between small">
                            <div class="text-muted">Copyright &copy; C&M Soluciones Abrasivas SAS 2026</div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>

        <!-- Scripts JavaScript JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="js/scripts.js"></script>
        <script src="js/datatables-simple-demo.js"></script>

        <!-- SCRIPT INTERACTIVO PARA EL CARRITO (SUMAR Y ELIMINAR) -->
        <script>
            function updateCartTotal() {
                let rows = document.querySelectorAll('#cartTableBody tr');
                let total = 0;
                let count = 0;

                rows.forEach(row => {
                    let priceText = row.children[1].innerText.replace('$', '').replace(/\./g, '').trim();
                    let qty = parseInt(row.querySelector('.item-qty').value);
                    let price = parseFloat(priceText);
                    
                    let subtotal = price * qty;
                    row.querySelector('.item-subtotal').innerText = '$ ' + subtotal.toLocaleString('es-CO');
                    total += subtotal;
                    count += qty;
                });

                document.getElementById('cartTotal').innerText = '$ ' + total.toLocaleString('es-CO');
                document.getElementById('cardItemCount').innerText = count + ' ítems';
                document.getElementById('cartBadge').innerText = count;
            }

            function proceedToPayment() {
                const rows = document.querySelectorAll('#cartTableBody tr');
                if (!rows.length) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Carrito vacío',
                        text: 'Agrega al menos un producto antes de proceder al pago.',
                        confirmButtonColor: '#198754'
                    });
                    return;
                }

                let total = 0;
                rows.forEach(row => {
                    const price = parseFloat(row.children[1].innerText.replace('$', '').replace(/\./g, '').replace(',', '.').trim()) || 0;
                    const quantity = parseInt(row.querySelector('.item-qty').value, 10) || 0;
                    total += price * quantity;
                });

                const invoiceNumber = 'FAC-PAGO-' + new Date().toISOString().replace(/[-:TZ.]/g, '').slice(0, 14) + '-' + Math.floor(100 + Math.random() * 900);
                window.open('index.php?action=pago_seguro&total=' + encodeURIComponent(total.toFixed(2)) + '&factura=' + encodeURIComponent(invoiceNumber), '_blank', 'noopener');
            }

            function changeQty(btn, delta) {
                let input = btn.parentElement.querySelector('.item-qty');
                let currentVal = parseInt(input.value);
                let newVal = currentVal + delta;
                let available = parseInt(btn.closest('tr').dataset.stock || '0');
                if (newVal >= 1 && newVal <= available) {
                    input.value = newVal;
                    updateCartTotal();
                } else if (delta > 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Stock insuficiente',
                        text: 'No hay más unidades disponibles de este producto.',
                        confirmButtonColor: '#ffc107',
                        confirmButtonText: 'Entendido'
                    });
                }
            }

            function removeItem(btn) {
                let row = btn.closest('tr');
                row.remove();
                updateCartTotal();
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function addToCart(productName, price, stock) {
                let tbody = document.getElementById('cartTableBody');
                
                // Verificar si ya existe para sumarle 1
                let existingRow = Array.from(tbody.querySelectorAll('tr')).find(row => row.children[0].innerText === productName);
                if (existingRow) {
                    let qtyInput = existingRow.querySelector('.item-qty');
                    let currentQty = parseInt(qtyInput.value);
                    if (currentQty >= stock) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stock insuficiente',
                            text: 'No hay más unidades disponibles de este producto.',
                            confirmButtonColor: '#ffc107',
                            confirmButtonText: 'Entendido'
                        });
                        return;
                    }
                    qtyInput.value = currentQty + 1;
                } else {
                    let newRow = document.createElement('tr');
                    newRow.dataset.stock = stock;
                    newRow.innerHTML = `
                        <td>${escapeHtml(productName)}</td>
                        <td>$ ${price.toLocaleString('es-CO')}</td>
                        <td class="item-stock">${stock}</td>
                        <td>
                            <div class="input-group input-group-sm">
                                <button class="btn btn-outline-secondary" type="button" onclick="changeQty(this, -1)">-</button>
                                <input type="text" class="form-control text-center item-qty" value="1" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="changeQty(this, 1)">+</button>
                            </div>
                        </td>
                        <td class="item-subtotal">$ ${price.toLocaleString('es-CO')}</td>
                        <td>
                            <button class="btn btn-danger btn-sm" onclick="removeItem(this)"><i class="fas fa-trash-alt"></i> Eliminar</button>
                        </td>
                    `;
                    tbody.appendChild(newRow);
                }
                updateCartTotal();
                Swal.fire({
                    icon: 'success',
                    title: 'Producto agregado',
                    text: 'El producto fue agregado al carrito exitosamente.',
                    confirmButtonColor: '#198754',
                    timer: 1800,
                    timerProgressBar: true
                });
            }

            function searchCatalogProduct(event) {
                if (event) event.preventDefault();

                const input = document.getElementById('catalogSearchInput');
                const query = input.value.trim().toLowerCase();
                const candidates = Array.from(document.querySelectorAll(
                    '#layoutSidenav_content .card, #layoutSidenav_content section, #layoutSidenav_content h1, #layoutSidenav_content h2, #layoutSidenav_content h3, #layoutSidenav_content h4, #layoutSidenav_content h5, #layoutSidenav_nav .nav-link'
                ));

                if (!query) {
                    candidates.forEach(element => element.classList.remove('border', 'border-primary', 'search-result-focus'));
                    return;
                }

                const matches = candidates.filter(element => element.textContent.toLowerCase().includes(query));

                if (!matches.length) {
                    Swal.fire({
                        icon: 'info',
                        title: 'No se encontró',
                        text: 'No encontramos información que coincida con "' + input.value.trim() + '" en este dashboard.',
                        confirmButtonColor: '#0d6efd'
                    });
                    return;
                }

                candidates.forEach(element => element.classList.remove('border', 'border-primary', 'search-result-focus'));
                matches[0].classList.add('border', 'border-primary', 'search-result-focus');
                matches[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            // La búsqueda del catálogo ahora es de servidor (GET ?action=cliente&q=).
            // Se conserva searchCatalogProduct por compatibilidad, con guard anti-nulos.
            const catalogSearchForm = document.getElementById('catalogSearchForm');
            if (catalogSearchForm) {
                catalogSearchForm.addEventListener('submit', searchCatalogProduct);
            }
        </script>
        <?php require __DIR__.'/partials/swal.php'; ?>
    </body>
</html>