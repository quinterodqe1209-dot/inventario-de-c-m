<?php
// controllers/PagoController.php — comprobantes de pago (lógica antes en index.php).

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/ComprobantePagoModel.php';

class PagoController extends BaseController
{
    private ComprobantePagoModel $model;

    public function __construct(?PDO $db = null)
    {
        $this->model = new ComprobantePagoModel($db ?? $this->db());
    }

    public function userCanSend(): bool
    {
        return in_array($_SESSION['rol'] ?? '', ['cliente', 'gerente', 'admin'], true);
    }

    public function userCanReview(): bool
    {
        return in_array($_SESSION['rol'] ?? '', ['gerente', 'admin'], true);
    }

    /** Valida + guarda comprobante. Devuelve mensaje (mismo texto que index.php). */
    public function enviar(array $post, array $file): string
    {
        $medio = trim($post['medio_pago'] ?? '');
        $monto = filter_var($post['monto'] ?? null, FILTER_VALIDATE_FLOAT);
        $referencia = trim($post['referencia'] ?? '');
        $direccion = trim($post['direccion_envio'] ?? '');
        $factura = trim($post['numero_factura'] ?? '');
        // Se conserva el catálogo original de medios (sin 'Banco de la Vivienda' en el form).
        $mediosValidos = ['Nequi', 'Bancolombia', 'Davivienda'];
        if (!(in_array($medio, $mediosValidos, true) && $monto !== false && $monto > 0 && $referencia !== '' && $direccion !== '' && preg_match('/^FAC-PAGO-[A-Z0-9-]+$/', $factura) && ($file['error'] ?? 1) === UPLOAD_ERR_OK && ($file['size'] ?? 0) <= 5242880)) {
            return 'Completa el medio, monto, referencia, dirección, productos del carrito y adjunta un comprobante de hasta 5 MB.';
        }
        $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset($tipos[$mime])) {
            return 'El comprobante debe ser PDF, JPG o PNG.';
        }
        $dir = dirname(__DIR__) . '/uploads/pagos';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $nombre = 'pago_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.' . $tipos[$mime];
        if (!move_uploaded_file($file['tmp_name'], $dir . DIRECTORY_SEPARATOR . $nombre)) {
            return 'No fue posible enviar el comprobante.';
        }
        $this->model->create((int) ($_SESSION['user']['id'] ?? 0), $medio, (float) $monto, $referencia, $direccion, $factura, 'uploads/pagos/' . $nombre);
        return 'Comprobante enviado al gerente para revisión.';
    }

    public function revisar(int $id, string $estado): void
    {
        if (in_array($estado, ['Aprobado', 'Rechazado'], true)) {
            $this->model->setEstado($id, $estado);
        }
    }

    public function pruebasGerente(): array
    {
        return $this->model->forGerente(30);
    }

    public function pruebasCliente(): array
    {
        return $this->model->forUser($this->uid(), 10);
    }
}
