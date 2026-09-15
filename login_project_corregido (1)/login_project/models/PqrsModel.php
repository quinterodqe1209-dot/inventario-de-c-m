<?php
// models/PqrsModel.php — extendido (antes solo updateStatus).
// Reúne SQL que estaba en views/pqrs_admin.php y views/clientes_dashboard.php.

require_once __DIR__ . '/../config/conexion.php';

class PqrsModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? (new Conexion())->conn;
    }

    public function ensureTable(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $this->db->exec("CREATE TABLE IF NOT EXISTS pqrs (
            id_pqrs INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            tipo VARCHAR(80) NOT NULL,
            descripcion TEXT NOT NULL,
            estado ENUM('Pendiente', 'En revisión', 'Resuelta', 'Cancelada') NOT NULL DEFAULT 'Pendiente',
            fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_pqrs_usuario (id_usuario),
            INDEX idx_pqrs_estado (estado)
        ) ENGINE=InnoDB");
    }

    public function updateStatus(int $idPqrs, string $estado): int
    {
        $stmt = $this->db->prepare('UPDATE pqrs SET estado = :estado WHERE id_pqrs = :id');
        $stmt->execute([':estado' => $estado, ':id' => $idPqrs]);
        return $stmt->rowCount();
    }

    public function listAdmin(string $filtroEstado = '', string $q = '', ?int $limit = null, int $offset = 0): array
    {
        $this->ensureTable();
        [$conds, $params] = $this->filtrosAdmin($filtroEstado, $q);
        $sql = 'SELECT p.id_pqrs, p.tipo, p.descripcion, p.estado, p.fecha_creacion, u.nombre, u.apellido, u.username, u.documento_id
             FROM pqrs p LEFT JOIN usuarios u ON u.id = p.id_usuario
             WHERE ' . implode(' AND ', $conds) . ' ORDER BY p.fecha_creacion DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarAdmin(string $filtroEstado = '', string $q = ''): int
    {
        $this->ensureTable();
        [$conds, $params] = $this->filtrosAdmin($filtroEstado, $q);
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM pqrs p LEFT JOIN usuarios u ON u.id = p.id_usuario WHERE ' . implode(' AND ', $conds));
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private function filtrosAdmin(string $filtroEstado, string $q): array
    {
        $conds = ['1=1'];
        $params = [];
        if ($filtroEstado !== '') {
            $conds[] = 'p.estado = ?';
            $params[] = $filtroEstado;
        }
        $q = trim(preg_replace('/\s+/', ' ', $q));
        if ($q !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $conds[] = '(p.descripcion LIKE ? OR p.tipo LIKE ? OR u.username LIKE ? OR u.nombre LIKE ? OR u.apellido LIKE ? OR CAST(p.id_pqrs AS CHAR) LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like);
        }
        return [$conds, $params];
    }

    public function counts(): array
    {
        $this->ensureTable();
        $estados = ['Pendiente', 'En revisión', 'Resuelta', 'Cancelada'];
        $out = [];
        foreach ($estados as $est) {
            $out[$est] = (int) $this->db->query('SELECT COUNT(*) FROM pqrs WHERE estado = ' . $this->db->quote($est))->fetchColumn();
        }
        return $out;
    }

    public function create(int $uid, string $tipo, string $descripcion): void
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('INSERT INTO pqrs (id_usuario, tipo, descripcion) VALUES (:id, :tipo, :desc)');
        $stmt->execute([':id' => $uid, ':tipo' => $tipo, ':desc' => $descripcion]);
    }

    public function mine(int $uid): array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT id_pqrs, tipo, descripcion, estado, fecha_creacion FROM pqrs WHERE id_usuario = :id ORDER BY fecha_creacion DESC');
        $stmt->execute([':id' => $uid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
