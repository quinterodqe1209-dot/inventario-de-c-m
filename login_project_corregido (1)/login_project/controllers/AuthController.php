<?php
// controllers/AuthController.php — MVC real: toda la autenticación pasa por aquí.
// index.php solo lo llama; las vistas login/register/recuperar/restablecer solo muestran $error/$mensaje.

require_once __DIR__ . '/UsuarioController.php';
require_once __DIR__ . '/../models/PasswordResetModel.php';

class AuthController
{
    private UsuarioController $usuarioController;
    private PasswordResetModel $resets;

    public function __construct()
    {
        $this->usuarioController = new UsuarioController();
        $this->resets = new PasswordResetModel();
    }

    public function login(string $username, string $password, string $rolSeleccionado): array
    {
        $user = $this->usuarioController->login($username, $password);
        if (!$user) {
            return ['success' => false, 'error' => 'Usuario o contraseña incorrectos.'];
        }
        $rolBd = strtolower(trim($user['rol'] ?? 'cliente'));
        $rolSeleccionado = strtolower(trim($rolSeleccionado));
        if ($rolSeleccionado !== $rolBd) {
            return ['success' => false, 'error' => "Acceso denegado: Tu cuenta no tiene permisos para el rol de '" . htmlspecialchars($rolSeleccionado) . "'."];
        }
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        $_SESSION['rol'] = $rolBd;
        return ['success' => true, 'role' => $rolBd];
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        header('Location: index.php?action=login');
        exit();
    }

    // ---- Registro público (misma validación que tenía index.php) ----
    public function register(array $post): array
    {
        $nombre = trim($post['nombre'] ?? '');
        $apellido = trim($post['apellido'] ?? '');
        $documento_id = trim($post['documento_id'] ?? '');
        $fecha_nacimiento = trim($post['fecha_nacimiento'] ?? '');
        $correo = trim($post['correo'] ?? '');
        $codigo = trim($post['codigo_verificacion'] ?? '');
        $username = trim($post['username'] ?? '');
        $password = trim($post['password'] ?? '');
        $rol = trim($post['rol'] ?? 'cliente');
        $roles_validos = ['cliente', 'proveedor'];

        if (!in_array($rol, $roles_validos, true)) {
            return ['ok' => false, 'error' => 'El rol seleccionado no es válido.'];
        }
        if ($nombre === '' || $apellido === '' || $documento_id === '' || $fecha_nacimiento === '' || $correo === '' || $username === '' || $password === '') {
            return ['ok' => false, 'error' => 'Por favor completa todos los campos.'];
        }
        if (preg_match('/^\d+$/', $documento_id) !== 1) {
            return ['ok' => false, 'error' => 'El documento de identidad solo debe contener números.'];
        }
        if (filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            return ['ok' => false, 'error' => 'El correo electrónico no es válido.'];
        }
        if (mb_strlen($password) < 6) {
            return ['ok' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres.'];
        }
        $v = $_SESSION['email_verification'] ?? null;
        $codigoOk = $v !== null && isset($v['email'], $v['code'], $v['expires_at'])
            && $v['email'] === $correo && (int) $v['expires_at'] >= time()
            && hash_equals((string) $v['code'], (string) $codigo);
        if (!$codigoOk) {
            return ['ok' => false, 'error' => 'Debes enviar y verificar el código enviado a tu correo antes de registrarte.'];
        }
        unset($_SESSION['email_verification']);
        if ($this->usuarioController->registrar($nombre, $apellido, $documento_id, $fecha_nacimiento, $correo, $username, $password, $rol)) {
            return ['ok' => true, 'error' => ''];
        }
        return ['ok' => false, 'error' => "No se pudo registrar. El usuario '" . htmlspecialchars($username) . "' ya existe o hubo un error en la base de datos."];
    }

    public function enviarCodigo(string $correo): array
    {
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Ingresa un correo electrónico válido.'];
        }
        $codigo = (string) random_int(100000, 999999);
        $_SESSION['email_verification'] = ['email' => $correo, 'code' => $codigo, 'expires_at' => time() + 300];
        $enviado = @mail($correo, 'Código de verificación - C&M Soluciones Abrasivas', "Tu código de verificación es: {$codigo}\n\nEste código expirará en 5 minutos.\n\nC&M Soluciones Abrasivas SAS", "From: noresponder@cmyabrasivas.com\r\nContent-Type: text/plain; charset=UTF-8");
        if ($enviado) {
            return ['success' => true, 'message' => 'Se envió el código de verificación a tu correo.'];
        }
        return ['success' => true, 'message' => 'No se pudo enviar automáticamente desde este servidor local. Usa este código de prueba: ' . $codigo, 'debug_code' => $codigo];
    }

    public function recuperar(string $correo): array
    {
        $out = ['mensajeRecuperar' => '', 'mensajeRecuperarError' => '', 'enlaceVisible' => ''];
        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $out['mensajeRecuperarError'] = 'Ingresa un correo electrónico válido.';
            return $out;
        }
        $usuario = $this->resets->findUserByEmail($correo);
        if (!$usuario) {
            $out['mensajeRecuperar'] = 'Si el correo ingresado está registrado, recibirás el enlace de recuperación.';
            return $out;
        }
        $token = $this->resets->createToken((int) $usuario['id']);
        $esquema = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') ? 'http' : 'https';
        $baseUrl = $esquema . '://' . $_SERVER['HTTP_HOST'] . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        $enlace = $baseUrl . '/index.php?action=restablecer&token=' . $token;
        $enviado = @mail($correo, 'Recuperación de contraseña - C&M Soluciones Abrasivas', "Hola {$usuario['username']},\n\nPara restablecer tu contraseña ingresa a:\n{$enlace}\n\nEl enlace expira en 30 minutos.\n\nC&M Soluciones Abrasivas SAS", "From: noresponder@cmyabrasivas.com\r\nContent-Type: text/plain; charset=UTF-8");
        if ($enviado) {
            $out['mensajeRecuperar'] = 'Te enviamos un enlace de recuperación a tu correo. Revisa tu bandeja de entrada (o spam).';
        } else {
            $out['mensajeRecuperar'] = 'No se pudo enviar el correo desde este servidor local. Usa el enlace de recuperación directamente:';
            $out['enlaceVisible'] = $enlace;
        }
        return $out;
    }

    public function validarToken(string $token): string
    {
        if ($token === '') {
            return 'Enlace de recuperación inválido.';
        }
        if (!$this->resets->findValid($token)) {
            return 'El enlace de recuperación no es válido, ya fue usado o expiró. Solicita uno nuevo.';
        }
        return '';
    }

    public function restablecer(string $token, string $p1, string $p2): string
    {
        if ($token === '') {
            return 'Enlace de recuperación inválido.';
        }
        if (mb_strlen($p1) < 6) {
            return 'La contraseña debe tener al menos 6 caracteres.';
        }
        if ($p1 !== $p2) {
            return 'Las contraseñas no coinciden.';
        }
        $fila = $this->resets->findValid($token);
        if (!$fila) {
            return 'El enlace de recuperación no es válido, ya fue usado o expiró. Solicita uno nuevo.';
        }
        $this->resets->consume($token, (int) $fila['id_usuario'], password_hash($p1, PASSWORD_BCRYPT));
        return '';
    }

    public static function homeParaRol(string $rol): string
    {
        switch ($rol) {
            case 'admin':
            case 'gerente': return 'gerente';
            case 'proveedor': return 'proveedor';
            case 'inventario': return 'inventario';
            case 'cliente': return 'cliente';
            default: return 'usuario&section=home';
        }
    }
}
