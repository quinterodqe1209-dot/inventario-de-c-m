<?php
// models/PasswordResetModel.php (antes SQL inline en index.php + views/restablecer.php).

require_once __DIR__ . '/../config/conexion.php';

class PasswordResetModel
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
        $this->db->exec("CREATE TABLE IF NOT EXISTS password_resets (
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
    }

    public function findUserByEmail(string $correo): ?array
    {
        $stmt = $this->db->prepare('SELECT id, username FROM usuarios WHERE correo = :correo AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([':correo' => $correo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createToken(int $uid): string
    {
        $this->ensureTable();
        $token = bin2hex(random_bytes(32));
        $this->db->prepare('UPDATE password_resets SET usado = 1 WHERE id_usuario = :id')->execute([':id' => $uid]);
        $this->db->prepare('INSERT INTO password_resets (id_usuario, token, expiracion) VALUES (:id, :token, DATE_ADD(NOW(), INTERVAL 30 MINUTE))')
            ->execute([':id' => $uid, ':token' => $token]);
        return $token;
    }

    public function findValid(string $token): ?array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT id_usuario FROM password_resets WHERE token = :token AND usado = 0 AND expiracion > NOW() LIMIT 1');
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function consume(string $token, int $uid, string $hash): void
    {
        $this->db->prepare('UPDATE usuarios SET password = :hash WHERE id = :id AND deleted_at IS NULL')
            ->execute([':hash' => $hash, ':id' => $uid]);
        $this->db->prepare('UPDATE password_resets SET usado = 1 WHERE token = :token')->execute([':token' => $token]);
    }
}
