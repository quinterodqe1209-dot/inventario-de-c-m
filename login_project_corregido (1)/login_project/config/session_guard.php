<?php
// config/session_guard.php
// Cierra la sesión automáticamente tras 15 minutos de inactividad.
// Debe incluirse DESPUÉS de session_start() y ANTES de leer/usar $_SESSION.

const SESSION_TIMEOUT_SECONDS = 15 * 60; // 15 minutos

/**
 * @param bool $renovarMarca true (default): cualquier petición cuenta como
 *                           actividad y renueva last_activity. false: revisa
 *                           si la sesión ya expiró pero NO renueva la marca
 *                           (p.ej. para el auto-refresco AJAX del dashboard,
 *                           para que un tab abierto no impida el cierre por
 *                           inactividad aunque el usuario no esté presente).
 */
function session_guard_check(bool $renovarMarca = true): void
{                                                                                                                                                                                                                                       
    if (!isset($_SESSION['user'])) {
        // No hay sesión activa que expirar.
        $_SESSION['last_activity'] = time();
        return;
    }

    $lastActivity = $_SESSION['last_activity'] ?? time();

    if ((time() - $lastActivity) > SESSION_TIMEOUT_SECONDS) {
        // Inactividad excedida: destruir sesión igual que en logout.
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        header('Location: index.php?action=login&mensaje=sesion_expirada');
        exit();
    }

    // Sesión sigue viva: renovar marca de tiempo (solo si es un clic real).
    if ($renovarMarca) {
        $_SESSION['last_activity'] = time();
    }
}
