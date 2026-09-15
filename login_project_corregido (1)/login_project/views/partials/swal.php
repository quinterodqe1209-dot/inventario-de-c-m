<?php
// views/partials/swal.php — SweetAlert2 global para todos los anuncios del sistema.
// Recoge automáticamente los mensajes flash de la vista ($mensaje_exito, $message,
// $mensaje, $notice, $msgPqrs, $pqrsMessage, $paymentMessage, $favoriteMessage y sus
// errores) y los muestra bonitos. También expone cmConfirmDelete() para los
// formularios de borrado (reemplaza al feo confirm() nativo).
// Uso: require __DIR__ . '/partials/swal.php'; antes de cerrar el body.

$__swalOk = '';
foreach (['swalSuccess', 'mensaje_exito', 'message', 'mensaje', 'notice', 'msgPqrs', 'pqrsMessage', 'paymentMessage', 'favoriteMessage'] as $__k) {
    if (!empty($$__k)) { $__swalOk = (string) $$__k; break; }
}
$__swalErr = '';
foreach (['swalError', 'error', 'errorRestablecer', 'error_lista', 'error_eliminar', 'error_editar', 'error_nuevo', 'error_perfil', 'error_password', 'dashboardError', 'pqrsError', 'mensajeRecuperarError'] as $__k) {
    if (!empty($$__k)) { $__swalErr = (string) $$__k; break; }
}
$__swalInfo = $swalInfo ?? ($mensajeRecuperar ?? '');
?>
<script>if (!window.Swal) { document.write('<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"><\/script>'); }</script>
<script>
(function () {
    var ok = <?php echo json_encode((string) $__swalOk, JSON_UNESCAPED_UNICODE); ?>;
    var err = <?php echo json_encode((string) $__swalErr, JSON_UNESCAPED_UNICODE); ?>;
    var info = <?php echo json_encode((string) $__swalInfo, JSON_UNESCAPED_UNICODE); ?>;
    function show(icon, title, text, timer) {
        if (!window.Swal) return;
        Swal.fire({
            icon: icon, title: title, text: text || undefined,
            timer: timer, timerProgressBar: true,
            showConfirmButton: timer === undefined,
            confirmButtonColor: '#f5c400'
        });
    }
    if (err) { show('error', 'Atención', err); }
    else if (ok) { show('success', 'Listo', ok, 2600); }
    else if (info) { show('info', 'Información', info, 3500); }

    // Confirmación bonita para borrados. Uso: onsubmit="return cmConfirmDelete(event,'¿Eliminar?')"
    window.cmConfirmDelete = function (ev, msg) {
        ev.preventDefault();
        var form = ev.target;
        if (!window.Swal) { if (confirm(msg)) form.submit(); return false; }
        Swal.fire({
            title: '¿Estás seguro?', text: msg || 'Esta acción no se puede deshacer.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
        }).then(function (r) { if (r.isConfirmed) form.submit(); });
        return false;
    };
})();
</script>
