<?php

require_once __DIR__ . '/../config/conexion.php';

class DashboardModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Conexion())->conn;
    }

    public function indicators(): array
    {
        $productStats = $this->db->query(
            'SELECT COUNT(*) AS products, COALESCE(SUM(PRO_stock_actual), 0) AS units, COALESCE(SUM(PRO_stock_actual <= PRO_stock_minimo), 0) AS low FROM productos WHERE deleted_at IS NULL'
        )->fetch(PDO::FETCH_ASSOC);
        $data = [
            'todaySales' => 0, 'weekSales' => 0, 'monthSales' => 0, 'pending' => 0,
            'products' => (int) ($productStats['products'] ?? 0),
            'stockUnits' => (int) ($productStats['units'] ?? 0),
            'low' => (int) ($productStats['low'] ?? 0),
            'weekly' => array_fill(0, 7, 0), 'monthly' => array_fill(0, 6, 0),
        ];
        $hasSales = (bool) $this->db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'ventas'")->fetchColumn();
        if (!$hasSales) return $data;
        $summary = $this->db->query("SELECT COALESCE(SUM(CASE WHEN DATE(fecha_venta)=CURRENT_DATE() AND estado <> 'Cancelada' THEN total ELSE 0 END),0) AS today_sales, COALESCE(SUM(CASE WHEN YEARWEEK(fecha_venta, 1)=YEARWEEK(CURRENT_DATE(), 1) AND estado <> 'Cancelada' THEN total ELSE 0 END),0) AS week_sales, COALESCE(SUM(CASE WHEN MONTH(fecha_venta)=MONTH(CURRENT_DATE()) AND YEAR(fecha_venta)=YEAR(CURRENT_DATE()) AND estado <> 'Cancelada' THEN total ELSE 0 END),0) AS month_sales FROM ventas WHERE deleted_at IS NULL")->fetch(PDO::FETCH_ASSOC);
        $data['todaySales'] = (float) ($summary['today_sales'] ?? 0);
        $data['weekSales'] = (float) ($summary['week_sales'] ?? 0);
        $data['monthSales'] = (float) ($summary['month_sales'] ?? 0);
        $data['pending'] = (int) $this->db->query("SELECT COALESCE(SUM(estado='Pendiente'),0) FROM ventas WHERE deleted_at IS NULL")->fetchColumn();
        foreach ($this->db->query("SELECT WEEKDAY(fecha_venta) AS day_index, SUM(total) AS amount FROM ventas WHERE fecha_venta >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 DAY) AND estado <> 'Cancelada' AND deleted_at IS NULL GROUP BY WEEKDAY(fecha_venta)") as $row) {
            $index = (int) $row['day_index']; if ($index >= 0 && $index < 7) $data['weekly'][$index] = (float) $row['amount'];
        }
        foreach ($this->db->query("SELECT PERIOD_DIFF(EXTRACT(YEAR_MONTH FROM CURRENT_DATE()), EXTRACT(YEAR_MONTH FROM fecha_venta)) AS month_index, SUM(total) AS amount FROM ventas WHERE fecha_venta >= DATE_SUB(CURRENT_DATE(), INTERVAL 5 MONTH) AND estado <> 'Cancelada' AND deleted_at IS NULL GROUP BY month_index") as $row) {
            $index = (int) $row['month_index']; if ($index >= 0 && $index < 6) $data['monthly'][5 - $index] = (float) $row['amount'];
        }
        return $data;
    }
}
