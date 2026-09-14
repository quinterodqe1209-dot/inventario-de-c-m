<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/PqrsModel.php';

class PqrsController
{
    private PqrsModel $model;

    public function __construct()
    {
        $this->model = new PqrsModel();
    }

    public const ACTIONS = ['actualizar_estado_pqrs'];

    private const ESTADOS_VALIDOS = [
        'Pendiente',
        'En revisión',
        'Resuelta',
        'Cancelada',
    ];

    public function database(): PDO
    {
        return (new Conexion())->conn;
    }

    public function canManage(): bool
    {
        return ($_SESSION['rol'] ?? '') === 'gerente';
    }

    public function isValidStatus(string $status): bool
    {
        return in_array($status, self::ESTADOS_VALIDOS, true);
    }

    public function updateStatus(int $idPqrs, string $estado): int
    {
        return $this->model->updateStatus($idPqrs, $estado);
    }
}
