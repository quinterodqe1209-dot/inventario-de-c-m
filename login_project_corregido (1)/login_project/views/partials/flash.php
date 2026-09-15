<?php
// views/partials/flash.php — mensajes success/error reutilizables (evita duplicar alerts).
// Uso: ['success' => $mensaje_exito ?? '', 'error' => $error ?? '']
$flashSuccess = $flashSuccess ?? ($mensaje_exito ?? ($mensaje ?? ($message ?? '')));
$flashError = $flashError ?? ($error ?? ($error_lista ?? ''));
?>
<?php if (!empty($flashSuccess)): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
<div class="alert alert-danger"><?php echo htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
