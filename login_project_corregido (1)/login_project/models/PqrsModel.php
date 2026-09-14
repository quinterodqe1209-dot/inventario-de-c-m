<?php

require_once __DIR__ . '/../config/conexion.php';

class PqrsModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Conexion())->conn;
    }

    public function updateStatus(int $idPqrs, string $estado): int
    {
        $stmt = $this->db->prepare(
            'UPDATE pqrs SET estado = :estado WHERE id_pqrs = :id'
        );
        $stmt->execute([
            ':estado' => $estado,
            ':id' => $idPqrs,
        ]);

        return $stmt->rowCount();
    }
}
