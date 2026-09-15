<?php
// core/Controller.php
// Clase base: acceso común a DB, sesión, roles y CSRF sin duplicar código.

require_once __DIR__ . '/View.php';

class BaseController
{
    protected function db(): PDO
    {
        require_once __DIR__ . '/../config/conexion.php';
        return (new Conexion())->conn;
    }

    protected function rol(): string
    {
        return $_SESSION['rol'] ?? '';
    }

    protected function uid(): int
    {
        return (int) ($_SESSION['user']['id'] ?? 0);
    }

    protected function requireRoles(array $roles): void
    {
        require_once __DIR__ . '/../config/require_auth.php';
        require_role($roles);
    }

    protected function verifyCsrf(): void
    {
        csrf_verify();
    }
}
