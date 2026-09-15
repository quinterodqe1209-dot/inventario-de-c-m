<?php
// controllers/DashboardController.php — resumen gerente + API JSON (antes duplicado en vista).

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/DashboardModel.php';
require_once __DIR__ . '/../models/ComprobantePagoModel.php';
require_once __DIR__ . '/../models/VentaModel.php';

class DashboardController extends BaseController
{
    private DashboardModel $model;
    private ComprobantePagoModel $pagos;
    private VentaModel $ventas;

    public function __construct()
    {
        $db = $this->db();
        $this->model = new DashboardModel();
        $this->pagos = new ComprobantePagoModel($db);
        $this->ventas = new VentaModel($db);
    }

    public function canRead(): bool
    {
        return isset($_SESSION['user']) && in_array($_SESSION['rol'] ?? '', ['gerente', 'inventario', 'admin'], true);
    }

    public function indicators(): array
    {
        return $this->model->indicators();
    }

    /** Datos para views/gerente_sbadm.php (mismas variables que la vista legacy). */
    public function datosGerente(): array
    {
        $user = $_SESSION['user'] ?? [];
        $data = [
            'username' => $user['username'] ?? 'Gerente', 'role' => $_SESSION['rol'] ?? 'gerente',
            'stats' => ['todaySales' => 0, 'weekSales' => 0, 'monthSales' => 0, 'stockUnits' => 0, 'pending' => 0, 'products' => 0, 'low' => 0],
            'weekly' => array_fill(0, 7, 0), 'monthly' => array_fill(0, 6, 0),
            'recentSales' => [], 'paymentProofs' => [], 'dashboardError' => '',
        ];
        try {
            $ind = $this->indicators();
            $data['stats'] = array_merge($data['stats'], $ind);
            $data['weekly'] = $ind['weekly'] ?? $data['weekly'];
            $data['monthly'] = $ind['monthly'] ?? $data['monthly'];
            $data['paymentProofs'] = $this->pagos->forGerente(30);
            $data['recentSales'] = $this->ventas->recientes(8);
        } catch (Throwable $e) {
            $data['dashboardError'] = 'No fue posible cargar los indicadores. Verifica la conexión y la estructura de la base de datos.';
        }
        $nombres = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $data['monthLabels'] = [];
        for ($i = 5; $i >= 0; $i--) {
            $data['monthLabels'][] = $nombres[(int) date('n', strtotime("-$i months")) - 1];
        }
        return $data;
    }
}
