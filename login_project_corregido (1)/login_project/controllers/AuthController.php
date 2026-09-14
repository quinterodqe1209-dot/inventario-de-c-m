<?php

require_once __DIR__ . '/UsuarioController.php';

class AuthController
{
    private UsuarioController $usuarioController;

    public function __construct()
    {
        $this->usuarioController = new UsuarioController();
    }

    public function login(string $username, string $password, string $rolSeleccionado): array
    {
        $user = $this->usuarioController->login($username, $password);

        if (!$user) {
            return [
                'success' => false,
                'error' => 'Usuario o contraseña incorrectos.',
            ];
        }

        $rolBd = strtolower(trim($user['rol'] ?? 'cliente'));
        $rolSeleccionado = strtolower(trim($rolSeleccionado));

        if ($rolSeleccionado !== $rolBd) {
            return [
                'success' => false,
                'error' => "Acceso denegado: Tu cuenta no tiene permisos para el rol de '" . htmlspecialchars($rolSeleccionado) . "'.",
            ];
        }

        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        $_SESSION['rol'] = $rolBd;

        return [
            'success' => true,
            'role' => $rolBd,
        ];
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        header('Location: index.php?action=login');
        exit();
    }
}
