<?php
// Archivo: index.php
require_once __DIR__ . "/controllers/UsuarioController.php";
require_once __DIR__ . "/controllers/AuthController.php";
require_once __DIR__ . "/controllers/PagoController.php";
require_once __DIR__ . "/controllers/ProveedorController.php";
require_once __DIR__ . "/controllers/PqrsController.php";
require_once __DIR__ . "/controllers/DashboardController.php";
require_once __DIR__ . "/controllers/InventarioController.php";
require_once "config/csrf.php";
require_once "config/session_guard.php";

ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Cierra la sesión automáticamente si pasaron más de 15 min sin actividad.
// Las peticiones de fondo (AJAX de auto-refresco del dashboard) SOLO verifican
// si la sesión ya expiró; NO renuevan last_activity. Así, un tab abierto con el
// dashboard no impide el cierre por inactividad mientras el usuario no actúe.
$accionActual = $_GET["action"] ?? null;
session_guard_check($accionActual !== "dashboard_data");

$controller = new UsuarioController();
$authController = new AuthController();
$pagoController = new PagoController();
$proveedorController = new ProveedorController();
$pqrsController = new PqrsController();
$dashboardController = new DashboardController();
$inventarioController = new InventarioController();

$error = "";
$mensaje = "";

// 1. Mensajes informativos por URL
if (isset($_GET["mensaje"]) && $_GET["mensaje"] === "registrado") {
    $mensaje = "¡Usuario registrado con éxito! Ya puedes iniciar sesión.";
} elseif (isset($_GET["mensaje"]) && $_GET["mensaje"] === "sesion_expirada") {
        $error = "Tu sesión expiró por inactividad. Vuelve a iniciar sesión.";
    } elseif (isset($_GET["mensaje"]) && $_GET["mensaje"] === "recuperado") {
        $mensaje = "¡Contraseña restablecida! Ya puedes iniciar sesión con tu nueva contraseña.";
    }

// 2. Procesar peticiones POST (Registro o Login)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    csrf_verify();

    if ($_POST["action"] === "enviar_codigo_verificacion") {
        header('Content-Type: application/json; charset=utf-8');
        $correo = trim($_POST["correo"] ?? '');

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Ingresa un correo electrónico válido.']);
            exit();
        }

        $codigo = (string) random_int(100000, 999999);
        $_SESSION['email_verification'] = [
            'email' => $correo,
            'code' => $codigo,
            'expires_at' => time() + 300,
        ];

        $asunto = 'Código de verificación - C&M Soluciones Abrasivas';
        $mensaje = "Tu código de verificación es: {$codigo}\n\nEste código expirará en 5 minutos.\n\nC&M Soluciones Abrasivas SAS";
        $headers = "From: noresponder@cmyabrasivas.com\r\nContent-Type: text/plain; charset=UTF-8";
        $enviado = @mail($correo, $asunto, $mensaje, $headers);

        if ($enviado) {
            echo json_encode(['success' => true, 'message' => 'Se envió el código de verificación a tu correo.']);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'No se pudo enviar automáticamente desde este servidor local. Usa este código de prueba: ' . $codigo,
                'debug_code' => $codigo,
            ]);
        }
        exit();
    }

    // ACCIÓN DE REGISTRO
    if ($_POST["action"] === "register") {
        $nombre = trim($_POST["nombre"] ?? '');
        $apellido = trim($_POST["apellido"] ?? '');
        $documento_id = trim($_POST["documento_id"] ?? '');
        $fecha_nacimiento = trim($_POST["fecha_nacimiento"] ?? '');
        $correo = trim($_POST["correo"] ?? '');
        $codigo_verificacion = trim($_POST["codigo_verificacion"] ?? '');
        $username = trim($_POST["username"] ?? '');
        $password = trim($_POST["password"] ?? '');
        $rol = trim($_POST["rol"] ?? 'cliente');
        // Roles que un usuario puede elegir al auto-registrarse.
        // Deben coincidir EXACTAMENTE con las <option> del <select> de
        // view/register.php y con el ENUM de la columna usuarios.rol
        // (gerente, admin e inventario quedan fuera a propósito: esos se
        // asignan desde el panel de usuarios, no por auto-registro).
        $roles_validos = ['cliente', 'proveedor'];

        if (!in_array($rol, $roles_validos, true)) {
            $error = "El rol seleccionado no es válido.";
            require_once "views/register.php";
            exit();
        }

        $documento_id_valido = preg_match('/^\d+$/', $documento_id) === 1;
        $correo_valido = filter_var($correo, FILTER_VALIDATE_EMAIL) !== false;
        $verificacion = $_SESSION['email_verification'] ?? null;
        $codigo_valido = $verificacion !== null
            && isset($verificacion['email'], $verificacion['code'], $verificacion['expires_at'])
            && $verificacion['email'] === $correo
            && (int) $verificacion['expires_at'] >= time()
            && hash_equals((string) $verificacion['code'], (string) $codigo_verificacion);

        if (!empty($nombre) && !empty($apellido) && !empty($documento_id) && !empty($fecha_nacimiento) && !empty($correo) && !empty($username) && !empty($password)) {
            if (!$documento_id_valido) {
                $error = "El documento de identidad solo debe contener números.";
                require_once "views/register.php";
                exit();
            }

            if (!$correo_valido) {
                $error = "El correo electrónico no es válido.";
                require_once "views/register.php";
                exit();
            }

            if (!$codigo_valido) {
                $error = "Debes enviar y verificar el código enviado a tu correo antes de registrarte.";
                require_once "views/register.php";
                exit();
            }

            unset($_SESSION['email_verification']);

            if ($controller->registrar($nombre, $apellido, $documento_id, $fecha_nacimiento, $correo, $username, $password, $rol)) {
                header("Location: index.php?action=login&mensaje=registrado");
                exit();
            } else {
                $error = "No se pudo registrar. El usuario '" . htmlspecialchars($username) . "' ya existe o hubo un error en la base de datos.";
                require_once "views/register.php";
                exit();
            }
        } else {
            $error = "Por favor completa todos los campos.";
                require_once "views/register.php";
            exit();
        }
    }

    // ACCIÓN RECUPERAR: genera un token para restablecer la contraseña
    if ($_POST["action"] === "recuperar") {
        if (isset($_SESSION["user"])) {
            header("Location: index.php");
            exit();
        }
        require_once "config/conexion.php";
        $db = (new Conexion())->conn;
        $db->exec("CREATE TABLE IF NOT EXISTS password_resets (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_usuario INT NOT NULL,
            token VARCHAR(64) NOT NULL,
            expiracion DATETIME NOT NULL,
            usado TINYINT(1) NOT NULL DEFAULT 0,
            creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_pwr_token (token),
            KEY idx_pwr_usuario (id_usuario)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $correo = trim($_POST["correo"] ?? '');
        $mensajeRecuperar = '';
        $mensajeRecuperarError = '';
        $enlaceVisible = '';
        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $mensajeRecuperarError = 'Ingresa un correo electrónico válido.';
        } else {
            $stmt = $db->prepare('SELECT id, username FROM usuarios WHERE correo = :correo AND deleted_at IS NULL LIMIT 1');
            $stmt->execute([':correo' => $correo]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                $token = bin2hex(random_bytes(32));
                $db->prepare('UPDATE password_resets SET usado = 1 WHERE id_usuario = :id_usuario')->execute([':id_usuario' => $usuario['id']]);
                $db->prepare('INSERT INTO password_resets (id_usuario, token, expiracion) VALUES (:id_usuario, :token, DATE_ADD(NOW(), INTERVAL 30 MINUTE))')
                    ->execute([':id_usuario' => $usuario['id'], ':token' => $token]);

                $esquema = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') ? 'http' : 'https';
                $baseUrl = $esquema . '://' . $_SERVER['HTTP_HOST'] . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
                $enlace = $baseUrl . '/index.php?action=restablecer&token=' . $token;

                $enviado = @mail(
                    $correo,
                    'Recuperación de contraseña - C&M Soluciones Abrasivas',
                    "Hola {$usuario['username']},\n\nPara restablecer tu contraseña ingresa a:\n{$enlace}\n\nEl enlace expira en 30 minutos.\n\nC&M Soluciones Abrasivas SAS",
                    "From: noresponder@cmyabrasivas.com\r\nContent-Type: text/plain; charset=UTF-8"
                );

                if ($enviado) {
                    $mensajeRecuperar = 'Te enviamos un enlace de recuperación a tu correo. Revisa tu bandeja de entrada (o spam).';
                } else {
                    $mensajeRecuperar = 'No se pudo enviar el correo desde este servidor local. Usa el enlace de recuperación directamente:';
                    $enlaceVisible = $enlace;
                }
            } else {
                $mensajeRecuperar = 'Si el correo ingresado está registrado, recibirás el enlace de recuperación.';
            }
        }
            require_once "views/recuperar.php";
        exit();
    }

    // ACCIÓN RESTABLECER: guarda la contraseña nueva usando el token
    if ($_POST["action"] === "restablecer") {
        if (isset($_SESSION["user"])) {
            header("Location: index.php");
            exit();
        }
        require_once "config/conexion.php";
        $db = (new Conexion())->conn;

        $token = trim($_POST["token"] ?? '');
        $password = trim($_POST["password"] ?? '');
        $password2 = trim($_POST["password2"] ?? '');
        $tokenRestablecer = $token;
        $errorRestablecer = '';

        if ($token === '' || mb_strlen($password) < 6 || $password !== $password2) {
            $errorRestablecer = $token === ''
                ? 'Enlace de recuperación inválido.'
                : (mb_strlen($password) < 6 ? 'La contraseña debe tener al menos 6 caracteres.' : 'Las contraseñas no coinciden.');
            require_once "views/restablecer.php";
            exit();
        }

        $stmt = $db->prepare('SELECT id_usuario FROM password_resets WHERE token = :token AND usado = 0 AND expiracion > NOW() LIMIT 1');
        $stmt->execute([':token' => $token]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            $errorRestablecer = 'El enlace de recuperación no es válido, ya fue usado o expiró. Solicita uno nuevo.';
            $tokenRestablecer = '';
            require_once "views/restablecer.php";
            exit();
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db->prepare('UPDATE usuarios SET password = :hash WHERE id = :id AND deleted_at IS NULL')
            ->execute([':hash' => $hash, ':id' => $fila['id_usuario']]);
        $db->prepare('UPDATE password_resets SET usado = 1 WHERE token = :token')->execute([':token' => $token]);

        header("Location: index.php?action=login&mensaje=recuperado");
        exit();
    }

    // ACCIÓN DE LOGIN CON VALIDACIÓN DE ROL EXACTO
    if ($_POST["action"] === "login") {
        $username = trim($_POST["username"] ?? '');
        $password = trim($_POST["password"] ?? '');
        $rol_seleccionado = trim($_POST["rol"] ?? '');

        $resultadoLogin = $authController->login($username, $password, $rol_seleccionado);
        if (!$resultadoLogin['success']) {
            $error = $resultadoLogin['error'];
            require_once "views/login.php";
            exit();
        }

        switch ($resultadoLogin['role']) {
            case 'admin':
            case 'gerente':
                header("Location: index.php?action=gerente");
                break;
            case 'proveedor':
                header("Location: index.php?action=proveedor");
                break;
            case 'inventario':
                header("Location: index.php?action=inventario");
                break;
            case 'cliente':
                header("Location: index.php?action=cliente");
                break;
            default:
                header("Location: index.php?action=usuario&section=home");
                break;
        }
        exit();
    }

    // ACCIÓN CLIENTE: ENVIAR COMPROBANTE DE PAGO AL GERENTE
    if ($_POST["action"] === "enviar_comprobante_pago") {
        if (!$pagoController->userCanSend()) { http_response_code(403); die('Sin permisos.'); }
        csrf_verify();
        require_once "config/conexion.php";
        $db = (new Conexion())->conn;
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
        $db->exec("ALTER TABLE comprobantes_pago MODIFY medio_pago ENUM('Nequi','Bancolombia','Davivienda','Banco de la Vivienda') NOT NULL");

        $medio = trim($_POST['medio_pago'] ?? '');
        $monto = filter_var($_POST['monto'] ?? null, FILTER_VALIDATE_FLOAT);
        $referencia = trim($_POST['referencia'] ?? '');
        $direccion = trim($_POST['direccion_envio'] ?? '');
        $numeroFactura = trim($_POST['numero_factura'] ?? '');
        $archivo = $_FILES['comprobante'] ?? null;
        $mediosValidos = ['Nequi', 'Bancolombia', 'Davivienda'];
        $mensajePago = 'No fue posible enviar el comprobante.';

        if (in_array($medio, $mediosValidos, true) && $monto !== false && $monto > 0 && $referencia !== '' && $direccion !== '' && preg_match('/^FAC-PAGO-[A-Z0-9-]+$/', $numeroFactura) && $archivo && $archivo['error'] === UPLOAD_ERR_OK && $archivo['size'] <= 5242880) {
            $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
            if (isset($tipos[$mime])) {
                $dir = __DIR__ . '/uploads/pagos';
                if (!is_dir($dir)) { mkdir($dir, 0755, true); }
                $nombre = 'pago_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.' . $tipos[$mime];
                if (move_uploaded_file($archivo['tmp_name'], $dir . DIRECTORY_SEPARATOR . $nombre)) {
                    $proofStmt = $db->prepare('INSERT INTO comprobantes_pago (id_usuario, medio_pago, monto, referencia, direccion_envio, numero_factura, comprobante) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $proofStmt->execute([(int) ($_SESSION['user']['id'] ?? 0), $medio, $monto, $referencia, $direccion, $numeroFactura, 'uploads/pagos/' . $nombre]);
                    $mensajePago = 'Comprobante enviado al gerente para revisión.';
                }
            } else {
                $mensajePago = 'El comprobante debe ser PDF, JPG o PNG.';
            }
        } else {
            $mensajePago = 'Completa el medio, monto, referencia, dirección, productos del carrito y adjunta un comprobante de hasta 5 MB.';
        }
        header("Location: index.php?action=cliente#pagos&mensaje_pago=" . urlencode($mensajePago));
        exit();
    }

    // ACCIÓN GERENTE: REVISAR COMPROBANTE DE PAGO
    if ($_POST["action"] === "actualizar_comprobante_pago") {
        if (!$pagoController->userCanReview()) { http_response_code(403); die('Sin permisos.'); }
        csrf_verify();
        require_once "config/conexion.php";
        $db = (new Conexion())->conn;
        $estado = trim($_POST['estado'] ?? '');
        if (in_array($estado, ['Aprobado', 'Rechazado'], true)) {
            $stmt = $db->prepare('UPDATE comprobantes_pago SET estado = ? WHERE id = ?');
            $stmt->execute([$estado, (int) ($_POST['comprobante_id'] ?? 0)]);
        }
        header('Location: index.php?action=gerente#comprobantes-pago');
        exit();
    }

    // ACCIÓN PROVEEDOR: ACTUALIZAR ESTADO DE UNA ORDEN DE COMPRA
    if ($_POST["action"] === "generar_orden_reabastecimiento") {
        if (!$proveedorController->canGenerateOrders()) { http_response_code(403); die('Sin permisos.'); }
        csrf_verify();
        require_once "config/conexion.php";
        $db = (new Conexion())->conn;
        $db->exec("CREATE TABLE IF NOT EXISTS orden_compra (ORD_id_orden INT NOT NULL, PVR_contacto VARCHAR(12) NOT NULL, ORD_fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ORD_estado VARCHAR(20) NOT NULL DEFAULT 'pendiente', ORD_total DECIMAL(10,2) DEFAULT NULL, ORD_retrasada TINYINT(1) NOT NULL DEFAULT 0, ORD_notas TEXT NULL) ENGINE=InnoDB");
        $db->exec("ALTER TABLE productos ADD COLUMN IF NOT EXISTS PRO_proveedor VARCHAR(150) NOT NULL DEFAULT 'Proveedor por asignar'");
        $db->exec("CREATE TABLE IF NOT EXISTS detalle_orden_compra (DOC_id INT NOT NULL, ORD_id_orden INT NOT NULL, PRO_codigo INT NOT NULL, DOC_cantidad INT NOT NULL, DOC_precio_unitario DECIMAL(10,2) NOT NULL) ENGINE=InnoDB");
        $productoId = (int) ($_POST['producto_id'] ?? 0);
        $productoStmt = $db->prepare('SELECT PRO_nombre_producto, PRO_stock_actual, PRO_stock_minimo, COALESCE(PRO_stock_maximo, PRO_stock_minimo * 2) AS stock_objetivo, PRO_costo_base, PRO_proveedor FROM productos WHERE PRO_codigo = ? AND deleted_at IS NULL');
        $productoStmt->execute([$productoId]);
        $producto = $productoStmt->fetch(PDO::FETCH_ASSOC);
        if (!$producto) { header('Location: index.php?action=proveedor&msg=' . urlencode('Producto no encontrado.')); exit(); }
        $cantidad = max(1, (int) $producto['stock_objetivo'] - (int) $producto['PRO_stock_actual']);
        $ordenId = (int) $db->query('SELECT COALESCE(MAX(ORD_id_orden), 0) + 1 FROM orden_compra')->fetchColumn();
        $detalleId = (int) $db->query('SELECT COALESCE(MAX(DOC_id), 0) + 1 FROM detalle_orden_compra')->fetchColumn();
        $total = $cantidad * (float) $producto['PRO_costo_base'];
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO orden_compra (ORD_id_orden, PVR_contacto, ORD_estado, ORD_total, ORD_notas) VALUES (?, ?, 'pendiente', ?, ?)");
            $stmt->execute([$ordenId, substr((string) $producto['PRO_proveedor'], 0, 12), $total, 'Reabastecimiento automático por stock bajo.']);
            $stmt = $db->prepare('INSERT INTO detalle_orden_compra (DOC_id, ORD_id_orden, PRO_codigo, DOC_cantidad, DOC_precio_unitario) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$detalleId, $ordenId, $productoId, $cantidad, $producto['PRO_costo_base']]);
            $db->commit();
            $msg = "Orden OC-$ordenId generada para {$producto['PRO_nombre_producto']}.";
        } catch (Throwable $exception) {
            if ($db->inTransaction()) { $db->rollBack(); }
            $msg = 'No fue posible generar la orden de reabastecimiento.';
        }
        header('Location: index.php?action=proveedor&msg=' . urlencode($msg) . '#alertas-stock');
        exit();
    }

    if ($_POST["action"] === "actualizar_orden") {
        $rol = $_SESSION['rol'] ?? '';
        if (!$proveedorController->canManageOrders()) { http_response_code(403); die('Sin permisos.'); }
        require_once "config/conexion.php";
        $db = (new Conexion())->conn;
        $db->exec("CREATE TABLE IF NOT EXISTS orden_compra (ORD_id_orden INT NOT NULL, PVR_contacto VARCHAR(12) NOT NULL, ORD_fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ORD_estado VARCHAR(20) NOT NULL DEFAULT 'pendiente', ORD_total DECIMAL(10,2) DEFAULT NULL, ORD_retrasada TINYINT(1) NOT NULL DEFAULT 0, ORD_notas TEXT NULL) ENGINE=InnoDB");
        $ordenId = (int) preg_replace('/[^0-9]/', '', trim($_POST['orden_id'] ?? ''));
        $grano = trim($_POST['avance'] ?? 'P40');
        $granoAEstado = ['P40' => 'pendiente', 'P80' => 'confirmado', 'P120' => 'en produccion', 'P180' => 'en transito', 'P220' => 'entregado'];
        $estado = $granoAEstado[$grano] ?? 'pendiente';
        $retrasada = (isset($_POST['retrasada']) && $_POST['retrasada'] === '1') ? 1 : 0;
        $notas = trim($_POST['notas'] ?? '');
        $msgOrden = 'Órden inválida.';
        if ($ordenId > 0) {
            try {
                $stmt = $db->prepare('UPDATE orden_compra SET ORD_estado = :estado, ORD_retrasada = :retrasada, ORD_notas = :notas WHERE ORD_id_orden = :id');
                $stmt->execute([':estado' => $estado, ':retrasada' => $retrasada, ':notas' => $notas !== '' ? $notas : null, ':id' => $ordenId]);
                $msgOrden = $stmt->rowCount() > 0 ? "OC-$ordenId actualizada a estado '$estado'." : 'La órden no existe o no cambió de estado.';
            } catch (Throwable $e) {
                $msgOrden = 'Error al actualizar la órden.';
            }
        }
        header("Location: index.php?action=proveedor&msg=" . urlencode($msgOrden));
        exit();
    }

    // ACCIÓN PROVEEDOR: AGENDAR ENTREGA
    if ($_POST["action"] === "agendar_entrega") {
        $rol = $_SESSION['rol'] ?? '';
        if (!$proveedorController->canManageOrders()) { http_response_code(403); die('Sin permisos.'); }
        require_once "config/conexion.php";
        $db = (new Conexion())->conn;
        $db->exec("CREATE TABLE IF NOT EXISTS despacho_bodega (DES_id INT NOT NULL, DES_direccion_envio VARCHAR(150) NOT NULL, DES_orden_bodega VARCHAR(50) NOT NULL, DES_id_confirmacion VARCHAR(50) NOT NULL, AUX_id INT DEFAULT NULL, estado VARCHAR(20) DEFAULT 'Pendiente', PED_id_pedido INT NOT NULL) ENGINE=InnoDB");
        $ordenRaw = trim($_POST['orden'] ?? '');
        $ordenEntrega = (int) preg_replace('/[^0-9]/', '', $ordenRaw);
        $fecha = trim($_POST['fecha'] ?? '');
        $destino = trim($_POST['destino'] ?? '');
        $msgEntrega = 'Completa la orden, la fecha y el destino.';
        if ($ordenEntrega > 0 && $fecha !== '' && $destino !== '') {
            try {
                $nextId = (int) $db->query('SELECT COALESCE(MAX(DES_id),0)+1 FROM despacho_bodega')->fetchColumn();
                $confirmacion = 'CONFIRM-' . strtoupper(bin2hex(random_bytes(5)));
                $estEntrega = (strtotime($fecha) <= strtotime(date('Y-m-d'))) ? 'Enviado' : 'Agendado';
                $stmt = $db->prepare('INSERT INTO despacho_bodega (DES_id, DES_direccion_envio, DES_orden_bodega, DES_id_confirmacion, AUX_id, estado, PED_id_pedido) VALUES (?,?,?,?,NULL,?,0)');
                $stmt->execute([$nextId, $destino, $ordenRaw, $confirmacion, $estEntrega]);
                $msgEntrega = "Entrega para $ordenRaw agendada (" . date('d/m/Y', strtotime($fecha)) . ") a $destino.";
            } catch (Throwable $e) {
                $msgEntrega = 'Error al agendar la entrega.';
            }
        }
        header("Location: index.php?action=proveedor&msg=" . urlencode($msgEntrega));
        exit();
    }

    // ACCIÓN PROVEEDOR: SUBIR FACTURA PDF/XML
    if ($_POST["action"] === "subir_factura") {
        $rol = $_SESSION['rol'] ?? '';
        if (!$proveedorController->canManageOrders()) { http_response_code(403); die('Sin permisos.'); }
        require_once "config/conexion.php";
        $db = (new Conexion())->conn;
        $db->exec("CREATE TABLE IF NOT EXISTS factura_orden_compra (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ORD_id_orden INT NOT NULL, archivo VARCHAR(255) NOT NULL, fecha_subida DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY idx_foc_orden (ORD_id_orden)) ENGINE=InnoDB");
        $ordenId = (int) preg_replace('/[^0-9]/', '', trim($_POST['orden_id'] ?? ''));
        $archivo = $_FILES['factura'] ?? null;
        $msgFactura = 'Archivo inválido: usa PDF o XML de hasta 5 MB.';
        if ($ordenId > 0 && $archivo && isset($archivo['error']) && (int) $archivo['error'] === UPLOAD_ERR_OK && (int) $archivo['size'] > 0 && (int) $archivo['size'] <= 5242880) {
            $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['pdf', 'xml'], true)) {
                try {
                    $dir = __DIR__ . '/uploads/facturas';
                    if (!is_dir($dir)) { mkdir($dir, 0755, true); }
                    $nombre = 'factura_OC' . $ordenId . '_' . date('Ymd_His') . '.' . $ext;
                    if (move_uploaded_file($archivo['tmp_name'], $dir . DIRECTORY_SEPARATOR . $nombre)) {
                        $stmt = $db->prepare('INSERT INTO factura_orden_compra (ORD_id_orden, archivo) VALUES (?,?)');
                        $stmt->execute([$ordenId, 'uploads/facturas/' . $nombre]);
                        $msgFactura = "Factura de OC-$ordenId subida correctamente.";
                    }
                } catch (Throwable $e) {
                    $msgFactura = 'Error al guardar la factura.';
                }
            }
        }
        header("Location: index.php?action=proveedor&msg=" . urlencode($msgFactura));
        exit();
    }

    // ACCIÓN ADMIN/GERENTE: CAMBIAR ESTADO DE UNA PQRS
    if ($_POST["action"] === "actualizar_estado_pqrs") {
        $rol = $_SESSION['rol'] ?? '';
        if (!$pqrsController->canManage()) { http_response_code(403); die('Sin permisos.'); }
        $idPqrs = (int) ($_POST['id_pqrs'] ?? 0);
        $estadoPqrs = trim($_POST['estado'] ?? '');
        $msgPqrs = 'Estado inválido.';
        if ($idPqrs > 0 && $pqrsController->isValidStatus($estadoPqrs)) {
            $updatedRows = $pqrsController->updateStatus($idPqrs, $estadoPqrs);
            $msgPqrs = $updatedRows > 0 ? 'PQRS #' . $idPqrs . ' actualizada a "' . $estadoPqrs . '".' : 'La PQRS no existe o no cambió de estado.';
        }
        header("Location: index.php?action=pqrs&msg=" . urlencode($msgPqrs));
        exit();
    }
}

// 3. Cierre de sesión
if (isset($_GET["action"]) && $_GET["action"] === "logout") {
    $authController->logout();
}

$roles_dashboard = ['gerente', 'inventario'];
if (isset($_GET["action"]) && $_GET["action"] === "dashboard_data" && $dashboardController->canRead()) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $data = $dashboardController->indicators();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    } catch (Throwable $exception) {
        http_response_code(500);
        echo json_encode(['error' => 'No fue posible actualizar los indicadores.']);
    }
    exit();
}

// Devuelve la vista "propia" de un rol, para usarla como destino seguro
// cuando la acción pedida no coincide con ninguna ruta válida para ese rol.
// Nunca debe apuntar a una vista con más privilegios que los del rol recibido.
function vista_home_para_rol(string $rol): string
{
    switch ($rol) {
        case 'gerente':
            return "views/gerente_sbadm.php";
        case 'inventario':
            return "views/inventario.php";
        case 'proveedor':
            return "views/proveedores_dashboard.php";
        case 'cliente':
        default:
            return "views/clientes_dashboard.php";
    }
}

// 4. Enrutamiento de Vistas (Usuarios autenticados)
if (isset($_SESSION["user"])) {
    require_once "config/conexion.php";
    $action = $_GET["action"] ?? 'usuario';
    $section = $_GET["section"] ?? 'home';
    $rol = $_SESSION["rol"] ?? 'cliente';

    if ($action === "pago_seguro" && in_array($rol, ['cliente', 'gerente'], true)) {
        require_once "views/pago_seguro.php";
    } elseif ($action === "cliente" && ($rol === "cliente" || $rol === "gerente")) {
        require_once "views/clientes_dashboard.php";
    } elseif ($action === "gerente" && $rol === "gerente") {
        require_once "views/gerente_sbadm.php";
    } elseif ($action === "proveedor" && ($rol === "proveedor" || $rol === "gerente")) {
        require_once "views/proveedores_dashboard.php";
    } elseif ($action === "pqrs" && $rol === "gerente") {
        require_once "views/pqrs_admin.php";
    } elseif ($action === "inventario" && in_array($rol, ["inventario", "gerente"], true)) {
        require_once "views/inventario.php";
    } elseif ($action === "reportes" && $rol === "gerente") {
        require_once "views/reportes.php";
    } elseif ($action === "usuario") {
        if ($section === "perfil") {
            require_once "views/perfil.php";
        } elseif ($section === "usuarios" && $rol === "gerente") {
            $vista_contenido = "views/usuarios.php";
            require_once "views/layout_panel.php";
        } elseif ($section === "nuevo_usuario" && $rol === "gerente") {
            $vista_contenido = "views/nuevo_usuario.php";
            require_once "views/layout_panel.php";
        } elseif ($section === "editar_usuario" && $rol === "gerente") {
            $vista_contenido = "views/editar_usuario.php";
            require_once "views/layout_panel.php";
        } elseif ($rol === "cliente") {
            require_once "views/clientes_dashboard.php";
        } elseif ($rol === "proveedor") {
            require_once "views/proveedores_dashboard.php";
        } elseif ($rol === "gerente") {
            require_once "views/gerente_sbadm.php";
        } elseif ($rol === "inventario") {
            require_once "views/inventario.php";
        } else {
            require_once "views/clientes_dashboard.php";
        }
    } else {
        // Antes: esto cargaba "view/gerente_sbadm.php" para CUALQUIER action
        // no reconocida, sin importar el rol -> un cliente pidiendo
        // ?action=reportes (rol no autorizado para esa ruta) terminaba
        // viendo el dashboard gerencial completo. Corregido: si la acción
        // pedida no es válida para el rol del usuario, lo mandamos a SU
        // propia vista, nunca a una con más privilegios.
        require_once vista_home_para_rol($rol);
    }
} else {
    $action = $_GET["action"] ?? 'login';

    if ($action === "register") {
        require_once "views/register.php";
    } elseif ($action === "recuperar") {
        require_once "views/recuperar.php";
    } elseif ($action === "restablecer") {
        require_once "views/restablecer.php";
    } else {
            require_once "views/login.php";
    }
}