<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/DashboardModel.php';

class DashboardController
{
    private DashboardModel $model;

    public function __construct()
    {
        $this->model = new DashboardModel();
    }

    public const ACTIONS = ['dashboard_data'];

    public function database(): PDO
    {
        return (new Conexion())->conn;
    }

    public function canRead(): bool
    {
        return isset($_SESSION['user'])
            && in_array($_SESSION['rol'] ?? '', ['gerente', 'inventario'], true);
    }

    public function indicators(): array
    {
        return $this->model->indicators();
    }
}
