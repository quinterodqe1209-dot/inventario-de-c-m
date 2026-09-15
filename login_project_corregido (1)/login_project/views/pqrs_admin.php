<?php
require_once __DIR__ . '/../config/require_auth.php';
require_role(['gerente','admin']);

require_once __DIR__ . '/../config/conexion.php';
// MVC: si el controlador ya entregó $listaPqrs/$conteos, solo presentar.
if (empty($__MVC_READY ?? null)) {
$db = (new Conexion())->conn;

$user = $_SESSION['user'] ?? [];
$username = $user['username'] ?? 'Usuario';
$role = $_SESSION['rol'] ?? 'gerente';
$msgPqrs = trim($_GET['msg'] ?? '');

$filtroEstado = trim($_GET['estado'] ?? '');
$estadosPqrs = ['Pendiente', 'En revisión', 'Resuelta', 'Cancelada'];

$condiciones = ['1=1'];
$parametros = [];
if ($filtroEstado !== '') {
    $condiciones[] = 'p.estado = ?';
    $parametros[] = $filtroEstado;
}

$sql = "SELECT p.id_pqrs, p.tipo, p.descripcion, p.estado, p.fecha_creacion,
            u.nombre, u.apellido, u.username, u.documento_id
        FROM pqrs p
        LEFT JOIN usuarios u ON u.id = p.id_usuario
        WHERE " . implode(' AND ', $condiciones) . "
        ORDER BY p.fecha_creacion DESC";
$stmt = $db->prepare($sql);
$stmt->execute($parametros);
$listaPqrs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$conteos = [];
foreach ($estadosPqrs as $est) {
    $conteos[$est] = (int) $db->query("SELECT COUNT(*) FROM pqrs WHERE estado = " . $db->quote($est))->fetchColumn();
}
} // fin legacy
// Defaults MVC.
$listaPqrs = $listaPqrs ?? [];
$conteos = $conteos ?? array_fill_keys($estadosPqrs ?? ['Pendiente','En revisión','Resuelta','Cancelada'], 0);
$msgPqrs = $msgPqrs ?? trim($_GET['msg'] ?? '');
$filtroEstado = $filtroEstado ?? trim($_GET['estado'] ?? '');
$busquedaPqrs = $busquedaPqrs ?? trim($_GET['q'] ?? '');
$paginaActual = $paginaActual ?? 1;
$totalPaginas = $totalPaginas ?? 1;
$totalPqrs = $totalPqrs ?? count($listaPqrs);
$estadosPqrs = $estadosPqrs ?? ['Pendiente', 'En revisión', 'Resuelta', 'Cancelada'];
$username = $username ?? ($_SESSION['user']['username'] ?? 'Usuario');
$role = $role ?? ($_SESSION['rol'] ?? 'gerente');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PQRS | C&M Soluciones Abrasivas</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <link href="css/admin-dark.css" rel="stylesheet">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        .pqrs-badge-en-revision { background: #ffc107; color: #212529; }
        .pqrs-badge-resuelta { background: #198754; }
        .pqrs-badge-cancelada { background: #dc3545; }
        .pqrs-badge-pendiente { background: #6c757d; }
        .desc-cell { max-width: 380px; white-space: normal; }
        #pqrsTable,
        #pqrsTable > :not(caption) > * > *,
        #pqrsTable tbody td,
        #pqrsTable tbody td small { color: #ffffff !important; }
        #pqrsTable tbody td small { color: #b8c7d1 !important; }
        #pqrsTable select { color: #ffffff; background-color: #101923; border-color: #34495a; }
        #pqrsTable select option { color: #ffffff; background-color: #101923; }
    </style>
</head>
<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="index.php?action=gerente">C&M ABRASIVAS</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button"><i class="fas fa-bars"></i></button>
        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4"><li class="nav-item dropdown"><a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown"><i class="fas fa-user fa-fw"></i> <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></a><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="index.php?action=usuario&section=perfil">Mi perfil</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item" href="index.php?action=logout">Cerrar sesión</a></li></ul></li></ul>
    </nav>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav"><nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion"><div class="sb-sidenav-menu"><div class="nav">
            <div class="sb-sidenav-menu-heading">Menú principal</div>
            <a class="nav-link" href="index.php?action=gerente"><div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>Dashboard</a>
            <div class="sb-sidenav-menu-heading">Gestión</div>
            <a class="nav-link" href="index.php?action=inventario"><div class="sb-nav-link-icon"><i class="fas fa-boxes"></i></div>Inventario</a>
            <a class="nav-link" href="index.php?action=reportes"><div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>Reportes</a>
            <a class="nav-link active" href="index.php?action=pqrs"><div class="sb-nav-link-icon"><i class="fas fa-headset"></i></div>PQRS</a>
            <a class="nav-link" href="index.php?action=usuario&section=usuarios"><div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>Usuarios</a>
            <div class="sb-sidenav-menu-heading">Mi cuenta</div><a class="nav-link" href="index.php?action=usuario&section=perfil"><div class="sb-nav-link-icon"><i class="fas fa-user-circle"></i></div>Mi perfil</a>
        </div></div><div class="sb-sidenav-footer"><div class="small">Inició sesión como:</div><?php echo htmlspecialchars(ucfirst($role), ENT_QUOTES, 'UTF-8'); ?></div></nav></div>
        <div id="layoutSidenav_content"><main><div class="container-fluid px-4">
            <h1 class="mt-4">Gestión de PQRS</h1>
            <ol class="breadcrumb mb-4"><li class="breadcrumb-item active">Centro de soporte y quejas de clientes</li></ol>

            <?php if ($msgPqrs !== ''): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($msgPqrs, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

            <div class="row mb-4">
                <?php foreach ($estadosPqrs as $est):
                    $badge = 'pqrs-badge-' . strtolower(str_replace(' ', '-', $est));
                ?>
                <div class="col-xl-3 col-md-6"><a class="text-decoration-none" href="index.php?action=pqrs&estado=<?php echo urlencode($est); ?>"><div class="card mb-3 border-0 shadow-sm <?php echo $filtroEstado === $est ? 'border border-warning' : ''; ?>"><div class="card-body"><span class="badge <?php echo $badge; ?> text-white"><?php echo htmlspecialchars($est, ENT_QUOTES, 'UTF-8'); ?></span><div class="fs-4 fw-bolder mt-2"><?php echo (int) $conteos[$est]; ?></div></div></div></a></div>
                <?php endforeach; ?>
                <div class="col-xl-3 col-md-6"><a class="text-decoration-none" href="index.php?action=pqrs"><div class="card mb-3 border-0 shadow-sm"><div class="card-body"><span class="badge bg-dark">Todos los estados</span><div class="fs-4 fw-bolder mt-2"><?php echo (int) array_sum($conteos); ?></div></div></div></a></div>
            </div>

            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span><i class="fas fa-headset me-1"></i> Solicitudes recibidas</span>
                    <form method="get" action="index.php" class="d-flex gap-2 align-items-center flex-wrap">
                        <input type="hidden" name="action" value="pqrs">
                        <select name="estado" class="form-select form-select-sm" style="width:auto">
                            <option value="">Todos los estados</option>
                            <?php foreach ($estadosPqrs as $est): ?>
                                <option value="<?php echo htmlspecialchars($est, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $filtroEstado === $est ? 'selected' : ''; ?>><?php echo htmlspecialchars($est, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="input-group input-group-sm" style="width:auto">
                            <input type="search" name="q" class="form-control" placeholder="Buscar cliente, tipo..." value="<?php echo htmlspecialchars($busquedaPqrs, ENT_QUOTES, 'UTF-8'); ?>">
                            <button class="btn btn-outline-primary" type="submit" title="Buscar"><i class="fas fa-search"></i></button>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" type="submit"><i class="fas fa-filter me-1"></i>Filtrar</button>
                        <?php if ($filtroEstado !== '' || $busquedaPqrs !== ''): ?><a href="index.php?action=pqrs" class="btn btn-sm btn-outline-secondary">Limpiar</a><?php endif; ?>
                    </form>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-bordered align-middle" id="pqrsTable">
                        <thead><tr><th>#</th><th>Cliente</th><th>Tipo</th><th>Descripción</th><th>Fecha</th><th>Estado</th><th></th></tr></thead>
                        <tbody>
                            <?php if (!$listaPqrs): ?>
                                <tr><td colspan="7" class="text-center text-muted">No hay solicitudes con estos criterios.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($listaPqrs as $pqrs):
                                $est = $pqrs['estado'] ?? 'Pendiente';
                                $badge = 'pqrs-badge-' . strtolower(str_replace(' ', '-', $est));
                            ?>
                            <tr>
                                <td><?php echo (int) $pqrs['id_pqrs']; ?></td>
                                <td><?php echo htmlspecialchars(trim(($pqrs['nombre'] ?? '') . ' ' . ($pqrs['apellido'] ?? '')), ENT_QUOTES, 'UTF-8'); ?><br><small class="text-muted"><?php echo htmlspecialchars($pqrs['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($pqrs['documento_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small></td>
                                <td><?php echo htmlspecialchars($pqrs['tipo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="desc-cell"><?php echo nl2br(htmlspecialchars($pqrs['descripcion'] ?? '', ENT_QUOTES, 'UTF-8')); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($pqrs['fecha_creacion'])); ?></td>
                                <td><span class="badge <?php echo $badge; ?> text-white"><?php echo htmlspecialchars($est, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td>
                                    <form method="post" action="index.php?action=pqrs" class="d-flex gap-1">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="actualizar_estado_pqrs">
                                        <input type="hidden" name="id_pqrs" value="<?php echo (int) $pqrs['id_pqrs']; ?>">
                                        <input type="hidden" name="estado_actual" value="<?php echo htmlspecialchars($est, ENT_QUOTES, 'UTF-8'); ?>">
                                        <select name="estado" class="form-select form-select-sm" style="width:auto">
                                            <?php foreach ($estadosPqrs as $estOpt): ?>
                                                <option value="<?php echo htmlspecialchars($estOpt, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $estOpt === $est ? 'selected' : ''; ?>><?php echo htmlspecialchars($estOpt, ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-primary" type="submit"><i class="fas fa-save"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php echo pager_html((int) $paginaActual, (int) $totalPaginas, ['action' => 'pqrs', 'estado' => $filtroEstado, 'q' => $busquedaPqrs]); ?>
                    <?php if ($totalPqrs > 0): ?><p class="text-muted small text-center mb-0">Mostrando <?php echo count($listaPqrs); ?> de <?php echo (int) $totalPqrs; ?> solicitudes · página <?php echo (int) $paginaActual; ?> de <?php echo (int) $totalPaginas; ?></p><?php endif; ?>
                </div>
            </div>
        </div></main><footer class="py-4 bg-light mt-auto"><div class="container-fluid px-4"><div class="small text-muted">C&M Soluciones Abrasivas SAS 2026</div></div></footer></div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php require __DIR__.'/partials/swal.php'; ?>
</body>
</html>