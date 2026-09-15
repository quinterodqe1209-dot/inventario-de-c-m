<?php
require_once __DIR__ . '/../config/require_auth.php';
require_role(['gerente', 'admin']);

// MVC: si el controlador ya entregó $usuario_editar, solo presentar.
if (empty($__MVC_READY ?? null)) {
// Procesar actualización de usuario (solo para administradores)
$usuario_editar = [];
$mensaje_exito = "";
$error_editar = "";

if (!in_array($_SESSION["rol"] ?? '', ["gerente", "admin"], true)) {
    header("Location: index.php?action=usuario&section=home");
    exit();
}

$id_usuario = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($id_usuario > 0) {
    try {
        $db = (new Conexion())->conn;
        
        // Obtener datos del usuario (excluye eliminados lógicamente)
        $query = "SELECT id, nombre, apellido, username, correo, rol, documento_id, fecha_nacimiento FROM usuarios WHERE id = :id AND deleted_at IS NULL";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id_usuario, PDO::PARAM_INT);
        $stmt->execute();
        $usuario_editar = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario_editar) {
            $error_editar = "Usuario no encontrado.";
        }
    } catch (Exception $e) {
        $error_editar = "Error: " . $e->getMessage();
    }
}

// Procesar actualización
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "actualizar_usuario") {
    csrf_verify();
    $id = intval($_POST["id"]);
    $nombre = trim($_POST["nombre"] ?? '');
    $apellido = trim($_POST["apellido"] ?? '');
    $documento_id = trim($_POST["documento_id"] ?? '');
    $fecha_nacimiento = trim($_POST["fecha_nacimiento"] ?? '');
    $correo = trim($_POST["correo"] ?? '');
    $rol = trim($_POST["rol"] ?? 'usuario');
    
    if (!empty($nombre) && !empty($apellido) && !empty($documento_id) && !empty($fecha_nacimiento) && !empty($correo)) {
        try {
            $db = (new Conexion())->conn;
            $query = "UPDATE usuarios SET nombre = :nombre, apellido = :apellido, documento_id = :documento_id, 
                     fecha_nacimiento = :fecha_nacimiento, correo = :correo, rol = :rol WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":apellido", $apellido);
            $stmt->bindParam(":documento_id", $documento_id);
            $stmt->bindParam(":fecha_nacimiento", $fecha_nacimiento);
            $stmt->bindParam(":correo", $correo);
            $stmt->bindParam(":rol", $rol);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $mensaje_exito = "Usuario actualizado correctamente.";
                $usuario_editar["nombre"] = $nombre;
                $usuario_editar["apellido"] = $apellido;
                $usuario_editar["documento_id"] = $documento_id;
                $usuario_editar["fecha_nacimiento"] = $fecha_nacimiento;
                $usuario_editar["correo"] = $correo;
                $usuario_editar["rol"] = $rol;
            } else {
                $error_editar = "Error al actualizar el usuario.";
            }
        } catch (Exception $e) {
            $error_editar = "Error: " . $e->getMessage();
        }
    } else {
        $error_editar = "Por favor completa todos los campos.";
    }
}
} // fin legacy
$usuario_editar = $usuario_editar ?? [];
$mensaje_exito = $mensaje_exito ?? '';
$error_editar = $error_editar ?? '';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Editar Usuario</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php?action=usuario&section=usuarios">Gestión de Usuarios</a></li>
        <li class="breadcrumb-item active">Editar Usuario</li>
    </ol>

    <?php if (!empty($mensaje_exito)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensaje_exito, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_editar)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_editar, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($usuario_editar)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit"></i>
            Datos del Usuario
        </div>
        <div class="card-body">
            <form method="POST" action="index.php?action=usuario&section=editar_usuario&id=<?php echo $usuario_editar["id"]; ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="actualizar_usuario">
                <input type="hidden" name="id" value="<?php echo $usuario_editar["id"]; ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" 
                               value="<?php echo htmlspecialchars($usuario_editar["nombre"]); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="apellido" class="form-label">Apellido</label>
                        <input type="text" class="form-control" id="apellido" name="apellido" 
                               value="<?php echo htmlspecialchars($usuario_editar["apellido"]); ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="documento_id" class="form-label">Documento de Identidad</label>
                        <input type="text" class="form-control" id="documento_id" name="documento_id" 
                               value="<?php echo htmlspecialchars($usuario_editar["documento_id"]); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" 
                               value="<?php echo htmlspecialchars($usuario_editar["fecha_nacimiento"]); ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="correo" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" 
                               value="<?php echo htmlspecialchars($usuario_editar["correo"]); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="rol" class="form-label">Rol</label>
                        <select class="form-select" id="rol" name="rol" required>
                            <option value="cliente" <?php echo $usuario_editar["rol"] === "cliente" ? "selected" : ""; ?>>Cliente</option>
                            <option value="proveedor" <?php echo $usuario_editar["rol"] === "proveedor" ? "selected" : ""; ?>>Proveedor</option>
                            <option value="inventario" <?php echo $usuario_editar["rol"] === "inventario" ? "selected" : ""; ?>>Inventario</option>
                            <option value="gerente" <?php echo $usuario_editar["rol"] === "gerente" ? "selected" : ""; ?>>Gerente</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label">Usuario (No se puede cambiar)</label>
                    <input type="text" class="form-control" id="username" 
                           value="<?php echo htmlspecialchars($usuario_editar["username"]); ?>" disabled>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <a href="index.php?action=usuario&section=usuarios" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> Usuario no encontrado o no tienes permiso para editarlo.
        </div>
        <a href="index.php?action=usuario&section=usuarios" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Usuarios
        </a>
    <?php endif; ?>
</div>
