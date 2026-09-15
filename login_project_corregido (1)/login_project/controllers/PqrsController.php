<?php
// controllers/PqrsController.php — ahora con listado/creación además del cambio de estado.

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/PqrsModel.php';

class PqrsController extends BaseController
{
    private PqrsModel $model;

    public function __construct()
    {
        $this->model = new PqrsModel($this->db());
    }

    private const ESTADOS_VALIDOS = ['Pendiente', 'En revisión', 'Resuelta', 'Cancelada'];

    public function canManage(): bool
    {
        return in_array($_SESSION['rol'] ?? '', ['gerente', 'admin'], true);
    }

    public function isValidStatus(string $status): bool
    {
        return in_array($status, self::ESTADOS_VALIDOS, true);
    }

    public function updateStatus(int $idPqrs, string $estado): int
    {
        return $this->model->updateStatus($idPqrs, $estado);
    }

    public function cambiarEstado(int $id, string $estado): string
    {
        if (!($id > 0 && $this->isValidStatus($estado))) {
            return 'Estado inválido.';
        }
        $n = $this->updateStatus($id, $estado);
        return $n > 0 ? 'PQRS #' . $id . ' actualizada a "' . $estado . '".' : 'La PQRS no existe o no cambió de estado.';
    }

    public function datosAdmin(string $filtro, string $q = '', int $page = 1, int $perPage = 10): array
    {
        $page = max(1, $page);
        $total = $this->model->contarAdmin($filtro, $q);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        return [
            'listaPqrs' => $this->model->listAdmin($filtro, $q, $perPage, ($page - 1) * $perPage),
            'conteos' => $this->model->counts(), 'filtroEstado' => $filtro, 'estadosPqrs' => self::ESTADOS_VALIDOS,
            'busquedaPqrs' => $q, 'paginaActual' => $page, 'totalPaginas' => $totalPages, 'totalPqrs' => $total,
        ];
    }

    public function crearDesdeCliente(string $tipo, string $descripcion): string
    {
        if (!in_array($tipo, ['Queja sobre producto', 'Reclamo por entrega', 'Sugerencia'], true) || $descripcion === '') {
            return 'Selecciona un tipo y escribe la descripción de tu solicitud.';
        }
        $this->model->create($this->uid(), $tipo, $descripcion);
        return 'OK:Tu PQRS fue enviada correctamente.';
    }
}
