<?php

require_once __DIR__ . '/../config/conexion.php';

class PagoController
{
    public const ACTIONS = [
        'enviar_comprobante_pago',
        'actualizar_comprobante_pago',
    ];

    public function database(): PDO
    {
        return (new Conexion())->conn;
    }

    public function userCanSend(): bool
    {
        return in_array($_SESSION['rol'] ?? '', ['cliente', 'gerente'], true);
    }

    public function userCanReview(): bool
    {
        return ($_SESSION['rol'] ?? '') === 'gerente';
    }
}
