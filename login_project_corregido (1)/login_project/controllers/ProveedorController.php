<?php

require_once __DIR__ . '/../config/conexion.php';

class ProveedorController
{
    public const ACTIONS = [
        'generar_orden_reabastecimiento',
        'actualizar_orden',
        'agendar_entrega',
        'subir_factura',
    ];

    public function database(): PDO
    {
        return (new Conexion())->conn;
    }

    public function canManageOrders(): bool
    {
        return in_array($_SESSION['rol'] ?? '', ['proveedor', 'gerente'], true);
    }

    public function canGenerateOrders(): bool
    {
        return ($_SESSION['rol'] ?? '') === 'gerente';
    }
}
