<?php

require_once __DIR__ . '/../config/conexion.php';

class InventarioController
{
    public const VIEWS = [
        'inventario' => 'views/inventario.php',
        'reportes' => 'views/reportes.php',
    ];

    public function database(): PDO
    {
        return (new Conexion())->conn;
    }

    public function canAccess(): bool
    {
        return in_array($_SESSION['rol'] ?? '', ['inventario', 'gerente'], true);
    }
}
