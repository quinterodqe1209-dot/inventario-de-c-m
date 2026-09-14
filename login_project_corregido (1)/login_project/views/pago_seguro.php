<?php
require_once __DIR__ . '/../config/require_auth.php';
require_role(['cliente', 'gerente']);

$total = max(0, (float) ($_GET['total'] ?? 0));
$invoiceNumber = preg_replace('/[^A-Z0-9-]/', '', strtoupper((string) ($_GET['factura'] ?? '')));
$invoiceNumber = preg_match('/^FAC-PAGO-[A-Z0-9-]+$/', $invoiceNumber) ? $invoiceNumber : 'FAC-PAGO-' . date('YmdHis');
$formattedTotal = number_format($total, 0, ',', '.');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pago seguro | C&M</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://use.fontawesome.com/releases/v6.3.0/css/all.css" rel="stylesheet">
    <style>
        body { background: #0a0f15; color: #f4f7fa; min-height: 100vh; }
        .payment-panel { max-width: 680px; margin: 5rem auto; }
        .payment-card { background: #17222d; border: 1px solid #334554; border-radius: 12px; }
        .payment-total { color: #ffc107; font-size: 2rem; font-weight: 700; }
        .invoice-number { background: #0f1722; border: 1px solid #536575; border-radius: 8px; color: #fff; font-weight: 700; letter-spacing: .04em; padding: .75rem 1rem; }
        .notice { background: #102b23; border-left: 4px solid #198754; }
        .btn-warning { color: #111; font-weight: 700; }
        .bank-link { border: 1px solid #536575; color: #fff; background: #101923; }
        .bank-link:hover { color: #ffc107; border-color: #ffc107; }
    </style>
</head>
<body>
<main class="container payment-panel">
    <div class="payment-card p-4 p-md-5 shadow">
        <div class="text-center mb-4"><i class="fas fa-shield-halved text-warning fa-3x mb-3"></i><h1 class="h3">Pago seguro</h1><p class="text-light opacity-75 mb-0">Continúa el pago únicamente desde la aplicación oficial.</p></div>
        <div class="text-center mb-4"><div class="small text-uppercase text-light opacity-75">Total del carrito</div><div class="payment-total">$ <?php echo htmlspecialchars($formattedTotal, ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="mb-4"><div class="small text-uppercase text-light opacity-75 mb-2">Número de factura</div><div class="invoice-number text-center"><?php echo htmlspecialchars($invoiceNumber, ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="notice rounded p-3 mb-4"><strong><i class="fas fa-circle-info me-2"></i>Protege tus datos</strong><p class="mb-0 mt-2">C&M nunca solicita tu contraseña, PIN ni código de seguridad. No los escribas en esta página ni los compartas con nadie.</p></div>
        <ol class="mb-4"><li class="mb-2">Elige tu entidad y abre únicamente su sitio o aplicación oficial.</li><li class="mb-2">Realiza el pago por el valor indicado.</li><li>Conserva el número de transacción para enviarlo junto con tu comprobante.</li></ol>
        <div class="d-grid gap-2 mb-3"><a class="btn btn-warning btn-lg" href="https://www.nequi.com.co/" target="_blank" rel="noopener"><i class="fas fa-mobile-screen-button me-2"></i>Nequi</a><a class="btn bank-link btn-lg" href="https://www.bancolombia.com/" target="_blank" rel="noopener"><i class="fas fa-building-columns me-2"></i>Bancolombia</a><a class="btn bank-link btn-lg" href="https://www.davivienda.com/" target="_blank" rel="noopener"><i class="fas fa-building-columns me-2"></i>Davivienda</a></div>
        <div class="d-grid gap-2"><a class="btn btn-success btn-lg" href="index.php?action=cliente&total_pago=<?php echo rawurlencode((string) $total); ?>&factura_pago=<?php echo rawurlencode($invoiceNumber); ?>#pagos"><i class="fas fa-receipt me-2"></i>Enviar comprobante al gerente</a><a class="btn btn-outline-light" href="index.php?action=cliente#carrito">Volver al carrito</a></div>
    </div>
</main>
</body>
</html>
