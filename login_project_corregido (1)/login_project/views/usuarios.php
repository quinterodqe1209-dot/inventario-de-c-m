<?php
require_once __DIR__ . '/../config/require_auth.php';
require_role(['gerente']);

// Procesar eliminación LÓGICA de usuario (soft delete: marca deleted_at)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "eliminar_usuario") {
    csrf_verify();
    $id_usuario = isset($_POST["id_usuario"]) ? intval($_POST["id_usuario"]) : 0;

    if ($id_usuario > 0) {
        try {
            $db = (new Conexion())->conn;

            // Verificar que no se pueda eliminar a sí mismo
            if ($id_usuario === $_SESSION["user"]["id"]) {
                $error_eliminar = "No puedes eliminar tu propia cuenta desde aquí.";
            } else {
                $query = "UPDATE usuarios SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id", $id_usuario, PDO::PARAM_INT);

                if ($stmt->execute() && $stmt->rowCount() > 0) {
                    $mensaje_exito = "Usuario eliminado correctamente.";
                } else {
                    $error_eliminar = "El usuario no existe o ya fue eliminado.";
                }
            }
        } catch (Exception $e) {
            $error_eliminar = "Error: " . $e->getMessage();
        }
    }
}

// --- Búsqueda, ordenamiento y paginación ---
$busqueda = trim($_GET["q"] ?? '');
$ordenPermitido = ['id', 'nombre', 'correo', 'rol'];
$orden = in_array($_GET["orden"] ?? '', $ordenPermitido, true) ? $_GET["orden"] : 'id';
$direccion = strtoupper($_GET["dir"] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$porPagina = 10;
$paginaActual = max(1, (int) ($_GET["pagina"] ?? 1));
$offset = ($paginaActual - 1) * $porPagina;

$usuarios = [];
$totalUsuarios = 0;
$totalPaginas = 1;

if ($_SESSION["rol"] === "gerente") {
    try {
        $db = (new Conexion())->conn;

        $where = "WHERE deleted_at IS NULL";
        $params = [];
        if ($busqueda !== '') {
            $where .= " AND (nombre LIKE :q OR apellido LIKE :q OR username LIKE :q OR correo LIKE :q)";
            $params[':q'] = '%' . $busqueda . '%';
        }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM usuarios $where");
        $countStmt->execute($params);
        $totalUsuarios = (int) $countStmt->fetchColumn();
        $totalPaginas = max(1, (int) ceil($totalUsuarios / $porPagina));

        $query = "SELECT id, nombre, apellido, username, correo, rol, fecha_nacimiento
                  FROM usuarios $where
                  ORDER BY $orden $direccion
                  LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Conteos globales (no solo la página actual) para las tarjetas de estadísticas
        $statsRow = $db->query("SELECT
                COUNT(*) AS total,
                SUM(rol IN ('gerente','admin')) AS gerentes,
                SUM(rol NOT IN ('admin','gerente')) AS estandar
            FROM usuarios WHERE deleted_at IS NULL")->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_lista = "Error al cargar usuarios: " . $e->getMessage();
    }
}
$statsRow = $statsRow ?? ['total' => 0, 'gerentes' => 0, 'estandar' => 0];

function usuarios_link_orden(string $campo, string $ordenActual, string $direccionActual, string $busqueda): string
{
    $nuevaDireccion = ($ordenActual === $campo && $direccionActual === 'ASC') ? 'DESC' : 'ASC';
    return 'index.php?action=usuario&section=usuarios&orden=' . urlencode($campo)
        . '&dir=' . $nuevaDireccion . '&q=' . urlencode($busqueda);
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Gestión de Usuarios</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Administrar usuarios del sistema</li>
    </ol>

    <?php if (!empty($mensaje_exito)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensaje_exito, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_eliminar)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_eliminar, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-users"></i>
            Lista de Usuarios
            <a href="index.php?action=usuario&section=nuevo_usuario" class="btn btn-sm btn-primary float-end">
                <i class="fas fa-user-plus"></i> Nuevo Usuario
            </a>
        </div>
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-2 mb-3">
                <input type="hidden" name="action" value="usuario">
                <input type="hidden" name="section" value="usuarios">
                <div class="col-md-6">
                    <input type="text" name="q" class="form-control" placeholder="Buscar por nombre, usuario o correo..."
                           value="<?php echo htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100"><i class="fas fa-search"></i> Buscar</button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th><a class="text-white text-decoration-none" href="<?php echo usuarios_link_orden('id', $orden, $direccion, $busqueda); ?>">ID <i class="fas fa-sort"></i></a></th>
                            <th><a class="text-white text-decoration-none" href="<?php echo usuarios_link_orden('nombre', $orden, $direccion, $busqueda); ?>">Nombre <i class="fas fa-sort"></i></a></th>
                            <th>Usuario</th>
                            <th><a class="text-white text-decoration-none" href="<?php echo usuarios_link_orden('correo', $orden, $direccion, $busqueda); ?>">Correo <i class="fas fa-sort"></i></a></th>
                            <th><a class="text-white text-decoration-none" href="<?php echo usuarios_link_orden('rol', $orden, $direccion, $busqueda); ?>">Rol <i class="fas fa-sort"></i></a></th>
                            <th>Fecha Nacimiento</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No hay usuarios registrados</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($usuario["id"]); ?></strong></td>
                                    <td><?php echo htmlspecialchars($usuario["nombre"] . " " . $usuario["apellido"]); ?></td>
                                    <td><?php echo htmlspecialchars($usuario["username"]); ?></td>
                                    <td><?php echo htmlspecialchars($usuario["correo"]); ?></td>
                                    <td>
                                        <?php 
                                        $rolBadge = $usuario["rol"];
                                        if ($rolBadge === "admin") {
                                            echo '<span class="badge bg-danger">Administrador</span>';
                                        } elseif ($rolBadge === "gerente") {
                                            echo '<span class="badge bg-warning">Gerente</span>';
                                        } elseif ($rolBadge === "inventario") {
                                            echo '<span class="badge bg-primary">Inventario</span>';
                                        } elseif ($rolBadge === "proveedor") {
                                            echo '<span class="badge bg-info text-dark">Proveedor</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary">Cliente</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($usuario["fecha_nacimiento"] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="index.php?action=usuario&section=editar_usuario&id=<?php echo $usuario["id"]; ?>" 
                                           class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <?php if ($usuario["id"] !== $_SESSION["user"]["id"]): ?>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                                    data-bs-target="#modalEliminar<?php echo $usuario["id"]; ?>" 
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>

                                            <!-- Modal de Confirmación -->
                                            <div class="modal fade" id="modalEliminar<?php echo $usuario["id"]; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-sm">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-danger text-white">
                                                            <h5 class="modal-title">
                                                                <i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación
                                                            </h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>¿Estás seguro de que deseas eliminar al usuario?</p>
                                                            <p><strong><?php echo htmlspecialchars($usuario["username"]); ?></strong></p>
                                                            <p class="text-muted"><small>Esta acción no se puede deshacer.</small></p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                            <form method="POST" action="index.php?action=usuario&section=usuarios" style="display: inline;">
                                                                <?php echo csrf_field(); ?>
                                                                <input type="hidden" name="action" value="eliminar_usuario">
                                                                <input type="hidden" name="id_usuario" value="<?php echo $usuario["id"]; ?>">
                                                                <button type="submit" class="btn btn-danger btn-sm">
                                                                    <i class="fas fa-trash"></i> Sí, Eliminar
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-secondary" title="No se puede eliminar tu propia cuenta">Tu Cuenta</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPaginas > 1): ?>
            <nav aria-label="Paginación de usuarios">
                <ul class="pagination justify-content-center mb-0">
                    <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
                        <li class="page-item <?php echo $p === $paginaActual ? 'active' : ''; ?>">
                            <a class="page-link" href="index.php?action=usuario&section=usuarios&pagina=<?php echo $p; ?>&orden=<?php echo urlencode($orden); ?>&dir=<?php echo $direccion; ?>&q=<?php echo urlencode($busqueda); ?>">
                                <?php echo $p; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <p class="text-center text-muted small mt-2"><?php echo $totalUsuarios; ?> usuario(s) encontrados.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <div class="card-title">Total de Usuarios</div>
                    <div class="fs-3 fw-bold"><?php echo (int) $statsRow['total']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <div class="card-title">Gerentes</div>
                    <div class="fs-3 fw-bold">
                        <?php echo (int) $statsRow['gerentes']; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <div class="card-title">Usuarios Estándar</div>
                    <div class="fs-3 fw-bold">
                        <?php echo (int) $statsRow['estandar']; ?>
                    </div>
                </div>
            </div>
        </div>
</div>
