<?php
// view/layout_panel.php
// Marco oscuro/industrial para las vistas-fragmento de gestión de usuarios.
// El router (index.php) define $vista_contenido (ruta PHP a incluir) ANTES de
// requerir este archivo. La vista se ejecuta en un buffer para que sus guards
// de sesión/rol (require_auth.php) puedan redirigir sin "headers already sent".

if (empty($vista_contenido)) {
    http_response_code(404);
    die('Vista no especificada.');
}

ob_start();
require $vista_contenido;
$contenidoPanel = ob_get_clean();

$usuarioPanel = $_SESSION['user']['username'] ?? 'Usuario';
$rolPanel     = $_SESSION['rol'] ?? '';
$tituloPanel  = match ($vista_contenido) {
    'views/usuarios.php'       => 'Gestión de Usuarios | C&M',
    'views/nuevo_usuario.php'  => 'Nuevo Usuario | C&M',
    'views/editar_usuario.php' => 'Editar Usuario | C&M',
    default                   => 'C&M Soluciones Abrasivas',
};
$activeUsuarios = in_array($vista_contenido, ['views/usuarios.php', 'views/nuevo_usuario.php', 'views/editar_usuario.php'], true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($tituloPanel, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <link href="css/admin-dark.css" rel="stylesheet">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="index.php?action=gerente">C&M ABRASIVAS</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button"><i class="fas fa-bars"></i></button>
        <form id="globalSearchForm" class="d-none d-md-inline-block form-inline ms-auto me-3 my-2 my-md-0">
            <div class="input-group">
                <input id="globalSearchInput" class="form-control" type="search" placeholder="Buscar en este dashboard..." aria-label="Buscar en este dashboard">
                <button class="btn btn-primary" type="submit" aria-label="Buscar"><i class="fas fa-search"></i></button>
            </div>
        </form>
        <ul class="navbar-nav ms-md-0 me-3 me-lg-4"><li class="nav-item dropdown"><a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown"><i class="fas fa-user fa-fw"></i> <?php echo htmlspecialchars($usuarioPanel, ENT_QUOTES, 'UTF-8'); ?></a><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="index.php?action=usuario&section=perfil">Mi perfil</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item" href="index.php?action=logout">Cerrar sesión</a></li></ul></li></ul>
    </nav>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav"><nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion"><div class="sb-sidenav-menu"><div class="nav">
            <div class="sb-sidenav-menu-heading">Menú principal</div>
            <a class="nav-link" href="index.php?action=gerente"><div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>Dashboard</a>
            <div class="sb-sidenav-menu-heading">Gestión</div>
            <a class="nav-link" href="index.php?action=inventario"><div class="sb-nav-link-icon"><i class="fas fa-boxes"></i></div>Inventario</a>
            <a class="nav-link" href="index.php?action=reportes"><div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>Reportes</a>
            <a class="nav-link" href="index.php?action=pqrs"><div class="sb-nav-link-icon"><i class="fas fa-headset"></i></div>PQRS</a>
            <a class="nav-link<?php echo $activeUsuarios ? ' active' : ''; ?>" href="index.php?action=usuario&section=usuarios"><div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>Usuarios</a>
            <div class="sb-sidenav-menu-heading">Mi cuenta</div><a class="nav-link" href="index.php?action=usuario&section=perfil"><div class="sb-nav-link-icon"><i class="fas fa-user-circle"></i></div>Mi perfil</a>
        </div></div><div class="sb-sidenav-footer"><div class="small">Inició sesión como:</div><?php echo htmlspecialchars(ucfirst($rolPanel), ENT_QUOTES, 'UTF-8'); ?></div></nav></div>
        <div id="layoutSidenav_content"><main>
            <?php echo $contenidoPanel; ?>
        </main><footer class="py-4 mt-auto"><div class="container-fluid px-4"><div class="small cm-footer-text">C&M Soluciones Abrasivas SAS 2026</div></div></footer></div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js"></script>
    <script>
        document.getElementById('globalSearchForm').addEventListener('submit', function (event) {
            event.preventDefault();
            const query = document.getElementById('globalSearchInput').value.trim().toLowerCase();
            const elements = Array.from(document.querySelectorAll('#layoutSidenav_content .card, #layoutSidenav_content section, #layoutSidenav_content h1, #layoutSidenav_content h2, #layoutSidenav_content h3, #layoutSidenav_content h4, #layoutSidenav_content h5, #layoutSidenav_nav .nav-link'));
            elements.forEach(element => element.classList.remove('border-primary'));

            if (!query) return;

            const match = elements.find(element => element.textContent.toLowerCase().includes(query));
            if (!match) {
                window.alert('No se encontró "' + document.getElementById('globalSearchInput').value.trim() + '" en este dashboard.');
                return;
            }

            match.classList.add('border', 'border-primary');
            match.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    </script>
</body>
</html>