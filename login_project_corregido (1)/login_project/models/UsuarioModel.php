<?php
// models/UsuarioModel.php — extiende models/usuario.php sin romper la clase Usuario existente.
// Reúne SQL que estaba en views/usuarios.php, editar_usuario.php, nuevo_usuario.php y perfil.php.

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/usuario.php';

class UsuarioModel extends Usuario
{
    private PDO $dbConn;

    public function __construct(?PDO $db = null)
    {
        // Usuario::__construct ya abre su propia conexión; reutilizamos una propia para el resto.
        if (!isset($this->db)) {
            parent::__construct();
        }
        $this->dbConn = $db ?? (new Conexion())->conn;
    }

    private function con(): PDO
    {
        return $this->dbConn;
    }

    public function listar(string $q, string $orden, string $dir, int $limit, int $offset): array
    {
        $where = 'WHERE deleted_at IS NULL';
        $params = [];
        if ($q !== '') {
            $where .= ' AND (nombre LIKE :q OR apellido LIKE :q OR username LIKE :q OR correo LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }
        $stmt = $this->con()->prepare(
            "SELECT id, nombre, apellido, username, correo, rol, fecha_nacimiento FROM usuarios $where ORDER BY $orden $dir LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contar(string $q): int
    {
        $where = 'WHERE deleted_at IS NULL';
        $params = [];
        if ($q !== '') {
            $where .= ' AND (nombre LIKE :q OR apellido LIKE :q OR username LIKE :q OR correo LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }
        $stmt = $this->con()->prepare("SELECT COUNT(*) FROM usuarios $where");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function stats(): array
    {
        $row = $this->con()->query(
            "SELECT COUNT(*) AS total, SUM(rol IN ('gerente','admin')) AS gerentes, SUM(rol NOT IN ('admin','gerente')) AS estandar FROM usuarios WHERE deleted_at IS NULL"
        )->fetch(PDO::FETCH_ASSOC);
        return ['total' => (int) ($row['total'] ?? 0), 'gerentes' => (int) ($row['gerentes'] ?? 0), 'estandar' => (int) ($row['estandar'] ?? 0)];
    }

    public function softDelete(int $id, int $selfId): string
    {
        if ($id <= 0) {
            return '';
        }
        if ($id === $selfId) {
            return 'No puedes eliminar tu propia cuenta desde aquí.';
        }
        $stmt = $this->con()->prepare('UPDATE usuarios SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            return 'OK:Usuario eliminado correctamente.';
        }
        return 'El usuario no existe o ya fue eliminado.';
    }

    public function findForEdit(int $id): ?array
    {
        $stmt = $this->con()->prepare('SELECT id, nombre, apellido, documento_id, fecha_nacimiento, correo, username, rol FROM usuarios WHERE id=:id AND deleted_at IS NULL');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateByAdmin(int $id, array $d): void
    {
        $stmt = $this->con()->prepare(
            'UPDATE usuarios SET nombre=:nombre, apellido=:apellido, documento_id=:doc, fecha_nacimiento=:fn, correo=:correo, username=:user, rol=:rol WHERE id=:id'
        );
        $stmt->execute([
            ':nombre' => $d['nombre'], ':apellido' => $d['apellido'], ':doc' => $d['documento_id'],
            ':fn' => $d['fecha_nacimiento'], ':correo' => $d['correo'], ':user' => $d['username'],
            ':rol' => $d['rol'], ':id' => $id,
        ]);
    }

    public function createByAdmin(array $d): bool
    {
        try {
            $stmt = $this->con()->prepare(
                'INSERT INTO usuarios (nombre,apellido,documento_id,fecha_nacimiento,correo,username,password,rol) VALUES (?,?,?,?,?,?,?,?)'
            );
            return $stmt->execute([
                $d['nombre'], $d['apellido'], $d['documento_id'], $d['fecha_nacimiento'],
                $d['correo'], $d['username'], password_hash($d['password'], PASSWORD_BCRYPT), $d['rol'],
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateProfile(int $id, array $d): bool
    {
        $stmt = $this->con()->prepare(
            'UPDATE usuarios SET nombre=:n, apellido=:a, documento_id=:doc, fecha_nacimiento=:fn, correo=:c WHERE id=:id'
        );
        return $stmt->execute([
            ':n' => $d['nombre'], ':a' => $d['apellido'], ':doc' => $d['documento_id'],
            ':fn' => $d['fecha_nacimiento'], ':c' => $d['correo'], ':id' => $id,
        ]);
    }

    public function changePassword(int $id, string $actual, string $nueva): string
    {
        $stmt = $this->con()->prepare('SELECT password FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $hash = $stmt->fetchColumn();
        if (!$hash || !password_verify($actual, $hash)) {
            return 'La contraseña actual no es correcta.';
        }
        $up = $this->con()->prepare('UPDATE usuarios SET password = :p WHERE id = :id');
        $up->execute([':p' => password_hash($nueva, PASSWORD_BCRYPT), ':id' => $id]);
        return 'OK:Contraseña actualizada correctamente.';
    }
}
