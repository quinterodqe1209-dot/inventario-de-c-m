<?php
require_once __DIR__ . '/../config/require_auth.php';
require_login(); // cualquier rol autenticado puede ver su propio perfil

// MVC: si el controlador ya entregó $perfil_cliente, solo presentar.
if (empty($__MVC_READY ?? null)) {
$perfil_cliente = [
    'USU_documento_identidad' => '',
    'PFL_historial_compra' => '',
    'PFL_productos_favoritos' => '',
    'PFL_fecha_compra' => '',
    'PFL_estado_pedidos' => '',
];
$productosFavoritos = [];

try {
    $db = (new Conexion())->conn;
    $stmt = $db->prepare('SELECT USU_documento_identidad, PFL_historial_compra, PFL_productos_favoritos, PFL_fecha_compra, PFL_estado_pedidos FROM perfil_cliente WHERE USU_documento_identidad = :documento LIMIT 1');
    $stmt->execute([':documento' => $_SESSION['user']['documento_id'] ?? '']);
    $perfil_cliente = array_merge($perfil_cliente, $stmt->fetch(PDO::FETCH_ASSOC) ?: []);

    $stmt = $db->prepare('SELECT p.PRO_nombre_producto, p.PRO_marca, p.PRO_precio_unitario FROM auditoria_favoritos af INNER JOIN productos p ON p.PRO_codigo = af.producto WHERE af.usuario = :usuario ORDER BY af.fecha DESC');
    $stmt->execute([':usuario' => $_SESSION['user']['documento_id'] ?? '']);
    $productosFavoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) {
    $perfil_cliente['USU_documento_identidad'] = $_SESSION['user']['documento_id'] ?? '';
}

// Procesar actualización de perfil
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "actualizar_perfil") {
    $nombre = trim($_POST["nombre"] ?? '');
    $apellido = trim($_POST["apellido"] ?? '');
    $documento_id = trim($_POST["documento_id"] ?? '');
    $fecha_nacimiento = trim($_POST["fecha_nacimiento"] ?? '');
    $correo = trim($_POST["correo"] ?? '');
    $id_usuario = $_SESSION["user"]["id"];

    if (!empty($nombre) && !empty($apellido) && !empty($documento_id) && !empty($fecha_nacimiento) && !empty($correo)) {
        try {
            $db = (new Conexion())->conn;
            $query = "UPDATE usuarios SET nombre = :nombre, apellido = :apellido, documento_id = :documento_id, 
                     fecha_nacimiento = :fecha_nacimiento, correo = :correo WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":apellido", $apellido);
            $stmt->bindParam(":documento_id", $documento_id);
            $stmt->bindParam(":fecha_nacimiento", $fecha_nacimiento);
            $stmt->bindParam(":correo", $correo);
            $stmt->bindParam(":id", $id_usuario);
            
            if ($stmt->execute()) {
                // Actualizar la sesión
                $_SESSION["user"]["nombre"] = $nombre;
                $_SESSION["user"]["apellido"] = $apellido;
                $_SESSION["user"]["documento_id"] = $documento_id;
                $_SESSION["user"]["fecha_nacimiento"] = $fecha_nacimiento;
                $_SESSION["user"]["correo"] = $correo;
                
                $mensaje_exito = "Perfil actualizado correctamente.";
            } else {
                $error_perfil = "Error al actualizar el perfil.";
            }
        } catch (Exception $e) {
            $error_perfil = "Error: " . $e->getMessage();
        }
    } else {
        $error_perfil = "Por favor completa todos los campos.";
    }
}

// Procesar cambio de contraseña
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "cambiar_password") {
    csrf_verify();
    $password_actual = trim($_POST["password_actual"] ?? '');
    $password_nueva = trim($_POST["password_nueva"] ?? '');
    $password_confirmar = trim($_POST["password_confirmar"] ?? '');
    $id_usuario = $_SESSION["user"]["id"];

    if ($password_actual === '' || $password_nueva === '' || $password_confirmar === '') {
        $error_password = "Completa todos los campos de la contraseña.";
    } elseif ($password_nueva !== $password_confirmar) {
        $error_password = "La nueva contraseña y su confirmación no coinciden.";
    } elseif (strlen($password_nueva) < 6) {
        $error_password = "La nueva contraseña debe tener al menos 6 caracteres.";
    } else {
        try {
            $db = (new Conexion())->conn;
            $stmt = $db->prepare('SELECT password FROM usuarios WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id_usuario]);
            $hashActual = $stmt->fetchColumn();

            if ($hashActual && password_verify($password_actual, $hashActual)) {
                $update = $db->prepare('UPDATE usuarios SET password = :password WHERE id = :id');
                $update->execute([':password' => password_hash($password_nueva, PASSWORD_BCRYPT), ':id' => $id_usuario]);
                $mensaje_exito = "Contraseña actualizada correctamente.";
            } else {
                $error_password = "La contraseña actual no es correcta.";
            }
        } catch (Exception $e) {
            $error_password = "Error: " . $e->getMessage();
        }
    }
}
} // fin legacy
// Defaults MVC.
$perfil_cliente = $perfil_cliente ?? ['USU_documento_identidad' => $_SESSION['user']['documento_id'] ?? '', 'PFL_historial_compra' => '', 'PFL_productos_favoritos' => '', 'PFL_fecha_compra' => '', 'PFL_estado_pedidos' => ''];
$productosFavoritos = $productosFavoritos ?? [];
$mensaje_exito = $mensaje_exito ?? '';
$error_perfil = $error_perfil ?? '';
$error_password = $error_password ?? '';
?>

<style>
    .profile-page {
        --profile-black: #17191c;
        --profile-yellow: #f5c400;
        --profile-white: #ffffff;
        --profile-gray: #f2f3f5;
        min-height: 100vh;
        padding: 2rem 1.5rem 3rem;
        background: var(--profile-gray);
        color: var(--profile-black);
    }

    .profile-page .profile-heading {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding: 1.5rem 1.75rem;
        border-left: 8px solid var(--profile-yellow);
        border-radius: 8px;
        background: var(--profile-black);
        color: var(--profile-white);
        box-shadow: 0 8px 18px rgba(0, 0, 0, .14);
    }

    .profile-page .profile-heading h1 {
        margin: 0;
        font-size: clamp(1.7rem, 3vw, 2.35rem);
        font-weight: 800;
    }

    .profile-page .profile-heading i {
        color: var(--profile-yellow);
        font-size: 2rem;
    }

    .profile-page .breadcrumb {
        margin: 0;
        color: #d7d7d7;
    }

    .profile-page .card {
        overflow: hidden;
        border: 1px solid #dedede;
        border-radius: 8px;
        background: var(--profile-white);
        box-shadow: 0 5px 14px rgba(0, 0, 0, .08);
    }

    .profile-page .card-header {
        border: 0;
        border-bottom: 4px solid var(--profile-yellow);
        background: var(--profile-black);
        color: var(--profile-white);
        font-weight: 700;
        letter-spacing: .02em;
    }

    .profile-page .card-header i {
        margin-right: .5rem;
        color: var(--profile-yellow);
    }

    .profile-page .form-label {
        color: var(--profile-black);
        font-size: .88rem;
        font-weight: 700;
    }

    .profile-page .form-control {
        border: 1px solid #c9c9c9;
        border-radius: 5px;
        background: #fff;
    }

    .profile-page .form-control:focus {
        border-color: var(--profile-yellow);
        box-shadow: 0 0 0 .2rem rgba(245, 196, 0, .25);
    }

    .profile-page .btn-primary,
    .profile-page .btn-warning {
        border-color: var(--profile-yellow);
        background: var(--profile-yellow);
        color: var(--profile-black);
        font-weight: 700;
    }

    .profile-page .btn-primary:hover,
    .profile-page .btn-warning:hover {
        border-color: #d6aa00;
        background: #d6aa00;
        color: var(--profile-black);
    }

    .profile-page .btn-secondary {
        border-color: var(--profile-black);
        background: var(--profile-black);
        color: var(--profile-white);
    }

    .profile-page .list-group-item {
        border-color: #e1e1e1;
        background: var(--profile-white);
    }

    .profile-page .badge.bg-info {
        background: var(--profile-yellow) !important;
        color: var(--profile-black) !important;
    }

    .profile-page .profile-value {
        color: #4d4d4d;
    }

    @media (max-width: 575.98px) {
        .profile-page {
            padding: 1rem .75rem 2rem;
        }

        .profile-page .profile-heading {
            padding: 1.15rem;
        }
    }
</style>

<div class="container-fluid profile-page">
    <div class="profile-heading">
        <i class="fas fa-user-circle"></i>
        <div>
            <h1>Mi Perfil</h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">Gestionar información personal</li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user"></i>
                    Información Personal
                </div>
                <div class="card-body">
                    <?php if (!empty($mensaje_exito)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensaje_exito, ENT_QUOTES, 'UTF-8'); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_perfil) || !empty($error_password)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_perfil ?? $error_password, ENT_QUOTES, 'UTF-8'); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php?action=usuario&section=perfil">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="actualizar_perfil">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?php echo htmlspecialchars($_SESSION["user"]["nombre"] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="apellido" class="form-label">Apellido</label>
                                <input type="text" class="form-control" id="apellido" name="apellido" 
                                       value="<?php echo htmlspecialchars($_SESSION["user"]["apellido"] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="documento_id" class="form-label">Documento de Identidad</label>
                                <input type="text" class="form-control" id="documento_id" name="documento_id" 
                                       value="<?php echo htmlspecialchars($_SESSION["user"]["documento_id"] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" 
                                       value="<?php echo htmlspecialchars($_SESSION["user"]["fecha_nacimiento"] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="correo" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="correo" name="correo" 
                                   value="<?php echo htmlspecialchars($_SESSION["user"]["correo"] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Usuario (No se puede cambiar)</label>
                            <input type="text" class="form-control" id="username" 
                                   value="<?php echo htmlspecialchars($_SESSION["user"]["username"] ?? ''); ?>" disabled>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <a href="index.php?action=usuario&section=home" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-shopping-bag"></i>
                    Información de Compras y Pedidos
                </div>
                <div class="card-body">
                    <p><strong>Documento asociado:</strong><br><?php echo htmlspecialchars($perfil_cliente['USU_documento_identidad'] ?: 'No registrado', ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Historial de compra:</strong><br><?php echo nl2br(htmlspecialchars($perfil_cliente['PFL_historial_compra'] ?: 'Sin compras registradas', ENT_QUOTES, 'UTF-8')); ?></p>
                    <p><strong>Productos favoritos:</strong></p>
                    <?php if ($productosFavoritos): ?>
                        <ul class="list-group mb-3">
                            <?php foreach ($productosFavoritos as $productoFavorito): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?php echo htmlspecialchars($productoFavorito['PRO_nombre_producto'], ENT_QUOTES, 'UTF-8'); ?><small class="d-block text-muted"><?php echo htmlspecialchars($productoFavorito['PRO_marca'] ?: 'C&M', ENT_QUOTES, 'UTF-8'); ?></small></span>
                                    <strong>$ <?php echo number_format((float) $productoFavorito['PRO_precio_unitario'], 0, ',', '.'); ?></strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p><?php echo htmlspecialchars($perfil_cliente['PFL_productos_favoritos'] ?: 'Sin productos favoritos', ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <p><strong>Fecha de compra:</strong><br><?php echo $perfil_cliente['PFL_fecha_compra'] ? date('d/m/Y', strtotime($perfil_cliente['PFL_fecha_compra'])) : 'Sin compras registradas'; ?></p>
                    <p class="mb-0"><strong>Estado de pedidos:</strong><br><span class="badge bg-info text-dark"><?php echo htmlspecialchars($perfil_cliente['PFL_estado_pedidos'] ?: 'Sin pedidos registrados', ENT_QUOTES, 'UTF-8'); ?></span></p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i>
                    Información de Cuenta
                </div>
                <div class="card-body">
                    <p><strong>Usuario:</strong> <?php echo htmlspecialchars($_SESSION["user"]["username"] ?? ''); ?></p>
                    <p><strong>Rol:</strong> 
                        <?php 
                        $rol = $_SESSION["rol"] ?? 'usuario';
                        $rol_display = match($rol) {
                            'admin' => '<span class="badge bg-danger">Administrador</span>',
                            'gerente' => '<span class="badge bg-warning">Gerente</span>',
                            default => '<span class="badge bg-info">Usuario</span>'
                        };
                        echo $rol_display;
                        ?>
                    </p>
                    <p><strong>Fecha de Registro:</strong> <?php echo date('d/m/Y'); ?></p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-lock"></i>
                    Seguridad
                </div>
                <div class="card-body">
                    <button class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#cambiarPasswordModal">
                        <i class="fas fa-key"></i> Cambiar Contraseña
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para cambiar contraseña -->
<div class="modal fade" id="cambiarPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Contraseña</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="index.php?action=usuario&section=perfil" id="cambiarPasswordForm">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="cambiar_password">
                    <div class="mb-3">
                        <label for="password_actual" class="form-label">Contraseña Actual</label>
                        <input type="password" class="form-control" id="password_actual" name="password_actual" required>
                    </div>
                    <div class="mb-3">
                        <label for="password_nueva" class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" id="password_nueva" name="password_nueva" minlength="6" required>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmar" class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" class="form-control" id="password_confirmar" name="password_confirmar" minlength="6" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="cambiarPasswordForm" class="btn btn-primary">Guardar Contraseña</button>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__.'/partials/swal.php'; ?>
