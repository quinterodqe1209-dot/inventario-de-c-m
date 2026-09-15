<?php
// index.php — Controlador Frontal MVC (delgado).
// Solo: bootstrap de sesión/CSRF, delegación a controladores y enrutado a vistas.
// NO contiene SQL ni reglas de negocio: todo vive en controllers/* y models/*.
// Se conservan intactos: nombres de ?action, campos POST/GET, tablas y mensajes.

require_once __DIR__ . '/core/View.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/controllers/UsuarioController.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/PagoController.php';
require_once __DIR__ . '/controllers/ProveedorController.php';
require_once __DIR__ . '/controllers/PqrsController.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/InventarioController.php';
require_once __DIR__ . '/controllers/ReporteController.php';
require_once __DIR__ . '/controllers/ClienteController.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/session_guard.php';

ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

$accionActual = $_GET['action'] ?? null;
session_guard_check($accionActual !== 'dashboard_data');

// Controladores (uno por dominio, como manda MVC).
$usuarioController = new UsuarioController();
$authController = new AuthController();
$pagoController = new PagoController();
$proveedorController = new ProveedorController();
$pqrsController = new PqrsController();
$dashboardController = new DashboardController();
$inventarioController = new InventarioController();
$reporteController = new ReporteController();
$clienteController = new ClienteController();

$error = '';
$mensaje = '';
if (($_GET['mensaje'] ?? '') === 'registrado') {
    $mensaje = '¡Usuario registrado con éxito! Ya puedes iniciar sesión.';
} elseif (($_GET['mensaje'] ?? '') === 'sesion_expirada') {
    $error = 'Tu sesión expiró por inactividad. Vuelve a iniciar sesión.';
} elseif (($_GET['mensaje'] ?? '') === 'recuperado') {
    $mensaje = '¡Contraseña restablecida! Ya puedes iniciar sesión con tu nueva contraseña.';
}

// ---------- POST: delegar al controlador dueño de cada acción ----------
// Se aceptan tanto `action` (auth/pagos/proveedor/pqrs/usuarios) como
// `product_action` (inventario) y `sale_action` (reportes), que no envían `action`.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) || isset($_POST['product_action']) || isset($_POST['sale_action']))) {
    if (isset($_POST['action'])) {
        csrf_verify();
    }
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'enviar_codigo_verificacion') {
        json_response($authController->enviarCodigo(trim($_POST['correo'] ?? '')));
    }

    if ($postAction === 'register') {
        $res = $authController->register($_POST);
        if ($res['ok']) {
            redirect('index.php?action=login&mensaje=registrado');
        }
        $error = $res['error'];
        view('views/register.php', compact('error'));
        exit();
    }

    if ($postAction === 'recuperar') {
        if (isset($_SESSION['user'])) {
            redirect('index.php');
        }
        $data = $authController->recuperar(trim($_POST['correo'] ?? ''));
        view('views/recuperar.php', $data);
        exit();
    }

    if ($postAction === 'restablecer') {
        if (isset($_SESSION['user'])) {
            redirect('index.php');
        }
        $token = trim($_POST['token'] ?? '');
        $errorRestablecer = $authController->restablecer($token, trim($_POST['password'] ?? ''), trim($_POST['password2'] ?? ''));
        if ($errorRestablecer === '') {
            redirect('index.php?action=login&mensaje=recuperado');
        }
        view('views/restablecer.php', ['errorRestablecer' => $errorRestablecer, 'tokenRestablecer' => $token]);
        exit();
    }

    if ($postAction === 'login') {
        $resultado = $authController->login(trim($_POST['username'] ?? ''), trim($_POST['password'] ?? ''), trim($_POST['rol'] ?? ''));
        if (!$resultado['success']) {
            $error = $resultado['error'];
            view('views/login.php', compact('error', 'mensaje'));
            exit();
        }
        redirect('index.php?action=' . AuthController::homeParaRol($resultado['role']));
    }

    if ($postAction === 'enviar_comprobante_pago') {
        if (!$pagoController->userCanSend()) {
            http_response_code(403);
            die('Sin permisos.');
        }
        csrf_verify();
        $mensajePago = $pagoController->enviar($_POST, $_FILES['comprobante'] ?? []);
        redirect('index.php?action=cliente#pagos&mensaje_pago=' . urlencode($mensajePago));
    }

    if ($postAction === 'actualizar_comprobante_pago') {
        if (!$pagoController->userCanReview()) {
            http_response_code(403);
            die('Sin permisos.');
        }
        csrf_verify();
        $pagoController->revisar((int) ($_POST['comprobante_id'] ?? 0), trim($_POST['estado'] ?? ''));
        redirect('index.php?action=gerente#comprobantes-pago');
    }

    if ($postAction === 'generar_orden_reabastecimiento') {
        if (!$proveedorController->canGenerateOrders()) {
            http_response_code(403);
            die('Sin permisos.');
        }
        csrf_verify();
        $msg = $proveedorController->generarReabastecimiento((int) ($_POST['producto_id'] ?? 0));
        redirect('index.php?action=proveedor&msg=' . urlencode($msg) . '#alertas-stock');
    }

    if ($postAction === 'actualizar_orden') {
        if (!$proveedorController->canManageOrders()) {
            http_response_code(403);
            die('Sin permisos.');
        }
        $msgOrden = $proveedorController->actualizarOrden($_POST);
        redirect('index.php?action=proveedor&msg=' . urlencode($msgOrden));
    }

    if ($postAction === 'agendar_entrega') {
        if (!$proveedorController->canManageOrders()) {
            http_response_code(403);
            die('Sin permisos.');
        }
        $msgEntrega = $proveedorController->agendarEntrega($_POST);
        redirect('index.php?action=proveedor&msg=' . urlencode($msgEntrega));
    }

    if ($postAction === 'subir_factura') {
        if (!$proveedorController->canManageOrders()) {
            http_response_code(403);
            die('Sin permisos.');
        }
        $msgFactura = $proveedorController->subirFactura($_POST, $_FILES['factura'] ?? []);
        redirect('index.php?action=proveedor&msg=' . urlencode($msgFactura));
    }

    if ($postAction === 'actualizar_estado_pqrs') {
        if (!$pqrsController->canManage()) {
            http_response_code(403);
            die('Sin permisos.');
        }
        $msgPqrs = $pqrsController->cambiarEstado((int) ($_POST['id_pqrs'] ?? 0), trim($_POST['estado'] ?? ''));
        $volverEstado = trim($_POST['estado_actual'] ?? '');
        redirect('index.php?action=pqrs' . ($volverEstado !== '' ? '&estado=' . urlencode($volverEstado) : '') . '&msg=' . urlencode($msgPqrs));
    }

    // Acciones que nacieron en las vistas y ahora tienen controlador dueño.
    if ($postAction === 'toggle_favorite') {
        $clienteController->toggleFavorito((int) ($_POST['product_id'] ?? 0));
        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php?action=cliente');
    }

    if ($postAction === 'crear_pqrs') {
        $r = $clienteController->crearPqrs(trim($_POST['tipo'] ?? ''), trim($_POST['descripcion'] ?? ''));
        // Se redirige para evitar reenvío; el listado se recarga desde el controlador.
        redirect('index.php?action=cliente&msg_pqrs=' . urlencode($r['msg']));
    }

    if ($postAction === 'eliminar_usuario') {
        csrf_verify();
        $msg = $usuarioController->eliminar();
        if (str_starts_with($msg, 'OK:') || $msg === '') {
            // Se re-renderiza el listado vía controlador (sin SQL en la vista).
            $data = $usuarioController->listadoAdmin();
            if ($msg !== '') {
                $data['mensaje_exito'] = substr($msg, 3);
            }
            $vista_contenido = 'views/usuarios.php';
            view('views/layout_panel.php', array_merge($data, compact('vista_contenido')));
            exit();
        } else {
            $data = $usuarioController->listadoAdmin();
            $data['error_eliminar'] = $msg;
            $vista_contenido = 'views/usuarios.php';
            view('views/layout_panel.php', array_merge($data, compact('vista_contenido')));
            exit();
        }
    }

    if (in_array($postAction, ['crear_usuario', 'actualizar_usuario'], true)) {
        csrf_verify();
        if ($postAction === 'crear_usuario') {
            $res = $usuarioController->crearPorAdmin($_POST);
            if (str_starts_with($res, 'OK:')) {
                view('views/layout_panel.php', ['vista_contenido' => 'views/nuevo_usuario.php', 'mensaje_exito' => substr($res, 3), 'error_nuevo' => '']);
            } else {
                view('views/layout_panel.php', ['vista_contenido' => 'views/nuevo_usuario.php', 'mensaje_exito' => '', 'error_nuevo' => $res]);
            }
            exit();
        } else {
            $res = $usuarioController->actualizarPorAdmin($_POST);
            $id = (int) ($_POST['id'] ?? 0);
            $datos = $usuarioController->datosParaEditar($id);
            if (str_starts_with($res, 'OK:')) {
                $datos['mensaje_exito'] = substr($res, 3);
                $datos['usuario_editar'] = $usuarioController->datosParaEditar($id)['usuario_editar'];
            } else {
                $datos['error_editar'] = $res;
            }
            view('views/layout_panel.php', array_merge($datos, ['vista_contenido' => 'views/editar_usuario.php']));
            exit();
        }
    }

    if (in_array($postAction, ['actualizar_perfil', 'cambiar_password'], true)) {
        if ($postAction === 'cambiar_password') {
            csrf_verify();
        }
        $datos = $usuarioController->datosPerfil();
        if ($postAction === 'actualizar_perfil') {
            $res = $usuarioController->actualizarPerfil($_POST);
            if (str_starts_with($res, 'OK:')) {
                $datos['mensaje_exito'] = substr($res, 3);
            } else {
                $datos['error_perfil'] = $res;
            }
        } else {
            $res = $usuarioController->cambiarPassword($_POST);
            if (str_starts_with($res, 'OK:')) {
                $datos['mensaje_exito'] = substr($res, 3);
            } else {
                $datos['error_password'] = $res;
            }
        }
        $datos = array_merge($datos, $usuarioController->datosPerfil());
        // Mantener el mensaje recién calculado.
        if (isset($res) && str_starts_with($res, 'OK:')) {
            $datos['mensaje_exito'] = substr($res, 3);
        } elseif (isset($res)) {
            $datos[$postAction === 'actualizar_perfil' ? 'error_perfil' : 'error_password'] = $res;
        }
        view('views/perfil.php', $datos);
        exit();
    }

    if (isset($_POST['product_action'])) {
        csrf_verify();
        // Formularios de inventario envían product_action=create/update/delete.
        $res = $inventarioController->guardar($_POST, $_FILES);
        $datos = $inventarioController->datos(trim($_GET['q'] ?? ''), max(1, (int) ($_GET['page'] ?? 1)));
        $datos['notice'] = $res[0];
        $datos['error'] = $res[1];
        view('views/inventario.php', $datos);
        exit();
    }

    if (isset($_POST['sale_action'])) {
        csrf_verify();
        $desdeP = trim($_GET['desde'] ?? '');
        $hastaP = trim($_GET['hasta'] ?? '');
        $qP = trim($_GET['q'] ?? '');
        $pageP = max(1, (int) ($_GET['page'] ?? 1));
        try {
            $message = $reporteController->guardar($_POST);
            $datos = $reporteController->datos($desdeP, $hastaP, $qP, $pageP);
            $datos['message'] = $message;
            view('views/reportes.php', $datos);
        } catch (Throwable $e) {
            $datos = $reporteController->datos($desdeP, $hastaP, $qP, $pageP);
            $datos['error'] = $e->getMessage();
            view('views/reportes.php', $datos);
        }
        exit();
    }
}

// ---------- GET especiales ----------
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $authController->logout();
}

if (isset($_GET['action']) && $_GET['action'] === 'dashboard_data' && $dashboardController->canRead()) {
    try {
        json_response($dashboardController->indicators());
    } catch (Throwable $e) {
        json_response(['error' => 'No fue posible actualizar los indicadores.'], 500);
    }
}

// API del panel proveedor: datos reales para hidratar el React sin romper el mock.
if (isset($_GET['action']) && $_GET['action'] === 'proveedor_data' && $proveedorController->canManageOrders()) {
    try {
        json_response($proveedorController->apiData());
    } catch (Throwable $e) {
        json_response(['error' => 'No fue posible cargar datos de proveedor.'], 500);
    }
}

// Migraciones desde navegador (solo gerente/admin): index.php?action=migrar
if (isset($_GET['action']) && $_GET['action'] === 'migrar' && in_array($_SESSION['rol'] ?? '', ['gerente', 'admin'], true)) {
    require_once __DIR__ . '/database/migrate.php';
    try {
        $log = migrar();
        header('Content-Type: text/html; charset=utf-8');
        echo '<div style="font-family:sans-serif;max-width:800px;margin:2rem auto;"><h1>Migración OK</h1><p>' . count($log) . ' sentencias aplicadas.</p><ul><li>' . implode('</li><li>', array_map(static fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8'), $log)) . '</li></ul><a href="index.php?action=gerente">Volver al dashboard</a></div>';
        exit();
    } catch (Throwable $e) {
        http_response_code(500);
        die('Error migrando: ' . htmlspecialchars($e->getMessage()));
    }
}

function vista_home_para_rol(string $rol): string
{
    switch ($rol) {
        case 'admin':
        case 'gerente': return 'views/gerente_sbadm.php';
        case 'inventario': return 'views/inventario.php';
        case 'proveedor': return 'views/proveedores_dashboard.php';
        case 'cliente':
        default: return 'views/clientes_dashboard.php';
    }
}

function accion_home_para_rol(string $rol): string
{
    switch ($rol) {
        case 'gerente': return 'gerente';
        case 'inventario': return 'inventario';
        case 'proveedor': return 'proveedor';
        case 'cliente':
        default: return 'cliente';
    }
}

// ---------- Enrutado de vistas (solo decide QUÉ mostrar, con datos del controlador) ----------
if (isset($_SESSION['user'])) {
    $action = $_GET['action'] ?? 'usuario';
    $section = $_GET['section'] ?? 'home';
    $rol = $_SESSION['rol'] ?? 'cliente';

    if ($action === 'pago_seguro' && in_array($rol, ['cliente', 'gerente'], true)) {
        $total = max(0, (float) ($_GET['total'] ?? 0));
        $factura = trim($_GET['factura'] ?? '');
        if ($factura !== '' && !preg_match('/^FAC-PAGO-[A-Z0-9-]+$/', $factura)) {
            $factura = '';
        }
        view('views/pago_seguro.php', ['total' => $total, 'invoiceNumber' => $factura, 'formattedTotal' => number_format($total, 0, ',', '.')]);
    } elseif ($action === 'cliente' && in_array($rol, ['cliente', 'gerente', 'admin'], true)) {
        $datos = $clienteController->datos(trim($_GET['q'] ?? ''), max(1, (int) ($_GET['page'] ?? 1)));
        if (trim($_GET['msg_pqrs'] ?? '') !== '') {
            $datos['pqrsMessage'] = trim($_GET['msg_pqrs']);
        }
        view('views/clientes_dashboard.php', $datos);
    } elseif ($action === 'gerente' && in_array($rol, ['gerente', 'admin'], true)) {
        view('views/gerente_sbadm.php', $dashboardController->datosGerente());
    } elseif ($action === 'proveedor' && in_array($rol, ['proveedor', 'gerente', 'admin'], true)) {
        view('views/proveedores_dashboard.php', $proveedorController->datosPanel());
    } elseif ($action === 'pqrs' && in_array($rol, ['gerente', 'admin'], true)) {
        $datos = $pqrsController->datosAdmin(trim($_GET['estado'] ?? ''), trim($_GET['q'] ?? ''), max(1, (int) ($_GET['page'] ?? 1)));
        $datos['msgPqrs'] = trim($_GET['msg'] ?? '');
        $datos['username'] = $_SESSION['user']['username'] ?? 'Usuario';
        $datos['role'] = $rol;
        view('views/pqrs_admin.php', $datos);
    } elseif ($action === 'inventario' && in_array($rol, ['inventario', 'gerente', 'admin'], true)) {
        $qInv = trim($_GET['q'] ?? '');
        $pageInv = max(1, (int) ($_GET['page'] ?? 1));
        // GET de escritura legacy (?product_action) se atiende vía controlador.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $res = $inventarioController->guardar($_POST, $_FILES);
            $datos = $inventarioController->datos($qInv, $pageInv);
            $datos['notice'] = $res[0];
            $datos['error'] = $res[1];
            view('views/inventario.php', $datos);
        } else {
            view('views/inventario.php', $inventarioController->datos($qInv, $pageInv));
        }
    } elseif ($action === 'reportes' && in_array($rol, ['gerente', 'admin'], true)) {
        $desde = trim($_GET['desde'] ?? '');
        $hasta = trim($_GET['hasta'] ?? '');
        $qVen = trim($_GET['q'] ?? '');
        $pageVen = max(1, (int) ($_GET['page'] ?? 1));
        // Exportaciones usan el listado COMPLETO filtrado (sin paginar).
        if (($_GET['export'] ?? '') === 'csv' || ($_GET['export'] ?? '') === 'pdf') {
            $full = $reporteController->listadoCompleto($desde, $hasta, $qVen);
            if (($_GET['export'] ?? '') === 'csv') {
                $reporteController->exportarCsv($full['sales']);
            } else {
                $reporteController->exportarPdf($full['sales'], (float) $full['totalSales'], $desde, $hasta);
            }
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $message = $reporteController->guardar($_POST);
                $datos = $reporteController->datos($desde, $hasta, $qVen, $pageVen);
                $datos['message'] = $message;
                view('views/reportes.php', $datos);
            } catch (Throwable $e) {
                $datos = $reporteController->datos($desde, $hasta, $qVen, $pageVen);
                $datos['error'] = $e->getMessage();
                view('views/reportes.php', $datos);
            }
        } else {
            view('views/reportes.php', $reporteController->datos($desde, $hasta, $qVen, $pageVen));
        }
    } elseif ($action === 'usuario') {
        if ($section === 'perfil') {
            view('views/perfil.php', $usuarioController->datosPerfil());
        } elseif ($section === 'usuarios' && in_array($rol, ['gerente', 'admin'], true)) {
            $vista_contenido = 'views/usuarios.php';
            view('views/layout_panel.php', array_merge($usuarioController->listadoAdmin(), compact('vista_contenido')));
        } elseif ($section === 'nuevo_usuario' && in_array($rol, ['gerente', 'admin'], true)) {
            $vista_contenido = 'views/nuevo_usuario.php';
            view('views/layout_panel.php', ['vista_contenido' => $vista_contenido, 'mensaje_exito' => '', 'error_nuevo' => '']);
        } elseif ($section === 'editar_usuario' && in_array($rol, ['gerente', 'admin'], true)) {
            $vista_contenido = 'views/editar_usuario.php';
            view('views/layout_panel.php', array_merge($usuarioController->datosParaEditar((int) ($_GET['id'] ?? 0)), compact('vista_contenido')));
        } elseif (in_array($rol, ['cliente', 'proveedor', 'gerente', 'inventario', 'admin'], true)) {
            redirect('index.php?action=' . accion_home_para_rol($rol));
        } else {
            view('views/clientes_dashboard.php', $clienteController->datos());
        }
    } else {
        view(vista_home_para_rol($rol), in_array($rol, ['gerente', 'admin'], true) ? $dashboardController->datosGerente() : ($rol === 'cliente' ? $clienteController->datos() : ($rol === 'inventario' ? $inventarioController->datos('') : [])));
    }
} else {
    $action = $_GET['action'] ?? 'login';
    if ($action === 'register') {
        view('views/register.php', ['error' => $error]);
    } elseif ($action === 'recuperar') {
        view('views/recuperar.php', ['mensajeRecuperar' => '', 'mensajeRecuperarError' => '', 'enlaceVisible' => '']);
    } elseif ($action === 'restablecer') {
        $token = trim($_GET['token'] ?? '');
        $errorRestablecer = $token === '' ? '' : $authController->validarToken($token);
        if ($errorRestablecer !== '') {
            $token = '';
        }
        view('views/restablecer.php', ['errorRestablecer' => $errorRestablecer, 'tokenRestablecer' => $token]);
    } else {
        view('views/login.php', ['error' => $error, 'mensaje' => $mensaje]);
    }
}
