<?php
// controllers/UsuarioController.php — gestiona usuarios + perfil (antes en views/*.php).

require_once __DIR__ . '/../models/usuario.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/ClientePortalModel.php';
require_once __DIR__ . '/../core/Controller.php';

class UsuarioController extends BaseController
{
    private Usuario $usuarioModel;
    private UsuarioModel $model;
    private ClientePortalModel $portal;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
        $this->model = new UsuarioModel();
        $this->portal = new ClientePortalModel();
    }

    public function login($username, $password)
    {
        return $this->usuarioModel->login($username, $password);
    }

    public function registrar($nombre, $apellido, $documento_id, $fecha_nacimiento, $correo, $username, $password, $rol = 'cliente')
    {
        return $this->usuarioModel->registrar($nombre, $apellido, $documento_id, $fecha_nacimiento, $correo, $username, $password, $rol);
    }

    // ---- Admin: listado con búsqueda/orden/paginación (mismo comportamiento) ----
    public function listadoAdmin(): array
    {
        $q = trim($_GET['q'] ?? '');
        $permitidos = ['id', 'nombre', 'correo', 'rol'];
        $orden = in_array($_GET['orden'] ?? '', $permitidos, true) ? $_GET['orden'] : 'id';
        $dir = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $porPagina = 10;
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $offset = ($pagina - 1) * $porPagina;
        $data = ['busqueda' => $q, 'orden' => $orden, 'direccion' => $dir, 'paginaActual' => $pagina, 'usuarios' => [], 'totalUsuarios' => 0, 'totalPaginas' => 1, 'statsRow' => ['total' => 0, 'gerentes' => 0, 'estandar' => 0], 'mensaje_exito' => '', 'error_eliminar' => '', 'error_lista' => ''];
        try {
            $total = $this->model->contar($q);
            $data['totalUsuarios'] = $total;
            $data['totalPaginas'] = max(1, (int) ceil($total / $porPagina));
            $data['usuarios'] = $this->model->listar($q, $orden, $dir, $porPagina, $offset);
            $data['statsRow'] = $this->model->stats();
        } catch (Exception $e) {
            $data['error_lista'] = 'Error al cargar usuarios: ' . $e->getMessage();
        }
        return $data;
    }

    public function eliminar(): string
    {
        $id = isset($_POST['id_usuario']) ? (int) $_POST['id_usuario'] : 0;
        if ($id <= 0) {
            return '';
        }
        $res = $this->model->softDelete($id, (int) ($_SESSION['user']['id'] ?? 0));
        if (str_starts_with($res, 'OK:')) {
            return substr($res, 3);
        }
        return $res; // mensaje de error (se muestra como $error_eliminar en la vista)
    }

    public function crearPorAdmin(array $post): string
    {
        $d = [
            'nombre' => trim($post['nombre'] ?? ''), 'apellido' => trim($post['apellido'] ?? ''),
            'documento_id' => trim($post['documento_id'] ?? ''), 'fecha_nacimiento' => trim($post['fecha_nacimiento'] ?? ''),
            'correo' => trim($post['correo'] ?? ''), 'username' => trim($post['username'] ?? ''),
            'password' => trim($post['password'] ?? ''), 'rol' => trim($post['rol'] ?? 'cliente'),
        ];
        if (!in_array($d['rol'], ['cliente', 'proveedor', 'inventario', 'gerente'], true)) {
            return 'El rol seleccionado no es válido.';
        }
        if (strlen($d['password']) < 6) {
            return 'La contraseña debe tener al menos 6 caracteres.';
        }
        if ($d['nombre'] === '' || $d['apellido'] === '' || $d['documento_id'] === '' || $d['fecha_nacimiento'] === '' || $d['correo'] === '' || $d['username'] === '') {
            return 'Por favor completa todos los campos.';
        }
        try {
            if ($this->model->createByAdmin($d)) {
                return 'OK:Usuario creado correctamente.';
            }
            return 'No se pudo crear el usuario. Verifica que el usuario y el correo no estén ya registrados.';
        } catch (Exception $e) {
            return str_contains($e->getMessage(), 'username') ? 'El nombre de usuario ya existe. Elige otro.' : 'Error al crear el usuario: verifica que el correo no esté duplicado.';
        }
    }

    public function datosParaEditar(int $id): array
    {
        return ['usuario_editar' => $this->model->findForEdit($id) ?? [], 'mensaje_exito' => '', 'error_editar' => $id > 0 && !$this->model->findForEdit($id) ? 'Usuario no encontrado.' : ''];
    }

    public function actualizarPorAdmin(array $post): string
    {
        $d = [
            'nombre' => trim($post['nombre'] ?? ''), 'apellido' => trim($post['apellido'] ?? ''),
            'documento_id' => trim($post['documento_id'] ?? ''), 'fecha_nacimiento' => trim($post['fecha_nacimiento'] ?? ''),
            'correo' => trim($post['correo'] ?? ''), 'username' => trim($post['username'] ?? ''),
            'rol' => trim($post['rol'] ?? 'cliente'),
        ];
        if ($d['nombre'] === '' || $d['apellido'] === '' || $d['documento_id'] === '' || $d['fecha_nacimiento'] === '' || $d['correo'] === '') {
            return 'Por favor completa todos los campos.';
        }
        try {
            $this->model->updateByAdmin((int) ($post['id'] ?? 0), $d);
            return 'OK:Usuario actualizado correctamente.';
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    // ---- Perfil propio ----
    public function datosPerfil(): array
    {
        $doc = $_SESSION['user']['documento_id'] ?? '';
        return ['perfil_cliente' => $this->portal->perfilCliente((string) $doc), 'productosFavoritos' => $this->portal->favoritosDetalle((string) $doc), 'mensaje_exito' => '', 'error_perfil' => '', 'error_password' => ''];
    }

    public function actualizarPerfil(array $post): string
    {
        $d = [
            'nombre' => trim($post['nombre'] ?? ''), 'apellido' => trim($post['apellido'] ?? ''),
            'documento_id' => trim($post['documento_id'] ?? ''), 'fecha_nacimiento' => trim($post['fecha_nacimiento'] ?? ''),
            'correo' => trim($post['correo'] ?? ''),
        ];
        if ($d['nombre'] === '' || $d['apellido'] === '' || $d['documento_id'] === '' || $d['fecha_nacimiento'] === '' || $d['correo'] === '') {
            return 'Por favor completa todos los campos.';
        }
        try {
            if ($this->model->updateProfile((int) $_SESSION['user']['id'], $d)) {
                foreach ($d as $k => $v) {
                    $_SESSION['user'][$k] = $v;
                }
                return 'OK:Perfil actualizado correctamente.';
            }
            return 'Error al actualizar el perfil.';
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function cambiarPassword(array $post): string
    {
        $a = trim($post['password_actual'] ?? '');
        $n = trim($post['password_nueva'] ?? '');
        $c = trim($post['password_confirmar'] ?? '');
        if ($a === '' || $n === '' || $c === '') {
            return 'Completa todos los campos de la contraseña.';
        }
        if ($n !== $c) {
            return 'La nueva contraseña y su confirmación no coinciden.';
        }
        if (strlen($n) < 6) {
            return 'La nueva contraseña debe tener al menos 6 caracteres.';
        }
        try {
            $res = $this->model->changePassword((int) $_SESSION['user']['id'], $a, $n);
            return $res;
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
}
