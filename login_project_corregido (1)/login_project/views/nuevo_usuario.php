<?php
require_once __DIR__ . '/../config/require_auth.php';
require_role(['gerente', 'admin']);

// MVC: si el controlador ya gestionó la creación, solo presentar.
if (empty($__MVC_READY ?? null)) {
$mensaje_exito = "";
$error_nuevo = "";

if (!in_array($_SESSION["rol"] ?? '', ["gerente", "admin"], true)) {
    header("Location: index.php?action=usuario&section=home");
    exit();
}

// Procesar creación de usuario (solo para administradores)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "crear_usuario") {
    csrf_verify();
    $nombre = trim($_POST["nombre"] ?? '');
    $apellido = trim($_POST["apellido"] ?? '');
    $documento_id = trim($_POST["documento_id"] ?? '');
    $fecha_nacimiento = trim($_POST["fecha_nacimiento"] ?? '');
    $correo = trim($_POST["correo"] ?? '');
    $username = trim($_POST["username"] ?? '');
    $password = trim($_POST["password"] ?? '');
    $rol = trim($_POST["rol"] ?? 'cliente');

    // Roles que el administrador puede asignar al crear una cuenta
    $roles_validos = ['cliente', 'proveedor', 'inventario', 'gerente'];

    if (!in_array($rol, $roles_validos, true)) {
        $error_nuevo = "El rol seleccionado no es válido.";
    } elseif (strlen($password) < 6) {
        $error_nuevo = "La contraseña debe tener al menos 6 caracteres.";
    } elseif (!empty($nombre) && !empty($apellido) && !empty($documento_id) && !empty($fecha_nacimiento) && !empty($correo) && !empty($username)) {
        try {
            $db = (new Conexion())->conn;
            $query = "INSERT INTO usuarios (nombre, apellido, documento_id, fecha_nacimiento, correo, username, password, rol)
                      VALUES (:nombre, :apellido, :documento_id, :fecha_nacimiento, :correo, :username, :password, :rol)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":apellido", $apellido);
            $stmt->bindParam(":documento_id", $documento_id);
            $stmt->bindParam(":fecha_nacimiento", $fecha_nacimiento);
            $stmt->bindParam(":correo", $correo);
            $stmt->bindParam(":username", $username);
            $stmt->bindValue(":password", password_hash($password, PASSWORD_BCRYPT));
            $stmt->bindParam(":rol", $rol);

            if ($stmt->execute()) {
                $mensaje_exito = "Usuario creado correctamente.";
            } else {
                $error_nuevo = "No se pudo crear el usuario. Verifica que el usuario y el correo no estén ya registrados.";
            }
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'username') !== false) {
                $error_nuevo = "El nombre de usuario ya existe. Elige otro.";
            } else {
                $error_nuevo = "Error al crear el usuario: verifica que el correo no esté duplicado.";
            }
        }
    } else {
        $error_nuevo = "Por favor completa todos los campos.";
    }
}
} // fin legacy
$mensaje_exito = $mensaje_exito ?? '';
$error_nuevo = $error_nuevo ?? '';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Nuevo Usuario</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php?action=usuario&section=usuarios">Gestión de Usuarios</a></li>
        <li class="breadcrumb-item active">Nuevo Usuario</li>
    </ol>

    <?php if (!empty($mensaje_exito)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensaje_exito, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_nuevo)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_nuevo, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user-plus"></i>
            Crear Usuario
        </div>
        <div class="card-body">
            <form method="POST" action="index.php?action=usuario&section=nuevo_usuario">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="crear_usuario">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="col-md-6">
                        <label for="apellido" class="form-label">Apellido</label>
                        <input type="text" class="form-control" id="apellido" name="apellido" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="documento_id" class="form-label">Documento de Identidad</label>
                        <input type="text" class="form-control" id="documento_id" name="documento_id" required>
                    </div>
                    <div class="col-md-6">
                        <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="correo" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" required>
                    </div>
                    <div class="col-md-6">
                        <label for="rol" class="form-label">Rol</label>
                        <select class="form-select" id="rol" name="rol" required>
                            <option value="cliente" selected>Cliente</option>
                            <option value="proveedor">Proveedor</option>
                            <option value="inventario">Inventario</option>
                            <option value="gerente">Gerente</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label">Usuario</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Usuario
                    </button>
                    <a href="index.php?action=usuario&section=usuarios" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>