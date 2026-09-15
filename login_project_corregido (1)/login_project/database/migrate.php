<?php
// database/migrate.php — Migración central idempotente.
// Ejecutar una vez: php database/migrate.php  (o visitar index.php?action=migrar como gerente).
// El runtime sigue siendo tolerante (ensure* con IF NOT EXISTS), pero ya no depende de DDL por-request.

require_once __DIR__ . '/../config/conexion.php';

function migrar(): array
{
    $db = (new Conexion())->conn;
    $log = [];
    $run = static function (string $sql) use ($db, &$log): void {
        $db->exec($sql);
        $log[] = mb_substr(preg_replace('/\s+/', ' ', trim($sql)), 0, 120);
    };

    $run("CREATE TABLE IF NOT EXISTS comprobantes_pago (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        medio_pago ENUM('Nequi','Bancolombia','Davivienda','Banco de la Vivienda') NOT NULL,
        monto DECIMAL(12,2) NOT NULL,
        referencia VARCHAR(100) NOT NULL,
        comprobante VARCHAR(255) NOT NULL,
        estado ENUM('Pendiente','Aprobado','Rechazado') NOT NULL DEFAULT 'Pendiente',
        fecha_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_comprobantes_estado (estado),
        INDEX idx_comprobantes_usuario (id_usuario)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $run('ALTER TABLE comprobantes_pago ADD COLUMN IF NOT EXISTS direccion_envio VARCHAR(200) NOT NULL DEFAULT \'\' AFTER referencia');
    $run('ALTER TABLE comprobantes_pago ADD COLUMN IF NOT EXISTS numero_factura VARCHAR(50) NOT NULL DEFAULT \'\' AFTER direccion_envio');

    $run("CREATE TABLE IF NOT EXISTS password_resets (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT, id_usuario INT NOT NULL, token VARCHAR(64) NOT NULL,
        expiracion DATETIME NOT NULL, usado TINYINT(1) NOT NULL DEFAULT 0,
        creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id), UNIQUE KEY uq_pwr_token (token), KEY idx_pwr_usuario (id_usuario)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $run("CREATE TABLE IF NOT EXISTS pqrs (
        id_pqrs INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, id_usuario INT NOT NULL, tipo VARCHAR(80) NOT NULL,
        descripcion TEXT NOT NULL, estado ENUM('Pendiente','En revisión','Resuelta','Cancelada') NOT NULL DEFAULT 'Pendiente',
        fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_pqrs_usuario (id_usuario), INDEX idx_pqrs_estado (estado)) ENGINE=InnoDB");

    $run("CREATE TABLE IF NOT EXISTS orden_compra (ORD_id_orden INT NOT NULL, PVR_contacto VARCHAR(12) NOT NULL, ORD_fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ORD_estado VARCHAR(20) NOT NULL DEFAULT 'pendiente', ORD_total DECIMAL(10,2) DEFAULT NULL, ORD_retrasada TINYINT(1) NOT NULL DEFAULT 0, ORD_notas TEXT NULL) ENGINE=InnoDB");
    $run('ALTER TABLE productos ADD COLUMN IF NOT EXISTS PRO_proveedor VARCHAR(150) NOT NULL DEFAULT \'Proveedor por asignar\'');
    $run('ALTER TABLE productos ADD COLUMN IF NOT EXISTS PRO_descripcion TEXT NULL AFTER PRO_nombre_producto');
    $run('ALTER TABLE productos ADD COLUMN IF NOT EXISTS PRO_marca VARCHAR(100) NULL AFTER PRO_descripcion');
    $run('ALTER TABLE productos ADD COLUMN IF NOT EXISTS PRO_imagen_url VARCHAR(255) NULL AFTER PRO_marca');
    $run('CREATE TABLE IF NOT EXISTS detalle_orden_compra (DOC_id INT NOT NULL, ORD_id_orden INT NOT NULL, PRO_codigo INT NOT NULL, DOC_cantidad INT NOT NULL, DOC_precio_unitario DECIMAL(10,2) NOT NULL) ENGINE=InnoDB');
    $run('CREATE TABLE IF NOT EXISTS despacho_bodega (DES_id INT NOT NULL, DES_direccion_envio VARCHAR(150) NOT NULL, DES_orden_bodega VARCHAR(50) NOT NULL, DES_id_confirmacion VARCHAR(50) NOT NULL, AUX_id INT DEFAULT NULL, estado VARCHAR(20) DEFAULT \'Pendiente\', PED_id_pedido INT NOT NULL) ENGINE=InnoDB');
    $run('CREATE TABLE IF NOT EXISTS factura_orden_compra (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ORD_id_orden INT NOT NULL, archivo VARCHAR(255) NOT NULL, fecha_subida DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY idx_foc_orden (ORD_id_orden)) ENGINE=InnoDB');

    $run("CREATE TABLE IF NOT EXISTS clientes (id_cliente INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,nombre VARCHAR(100) NOT NULL,apellido VARCHAR(100) NOT NULL,documento VARCHAR(40) NULL UNIQUE,estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo') ENGINE=InnoDB");
    $run("CREATE TABLE IF NOT EXISTS ventas (id_venta INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,id_cliente INT UNSIGNED NOT NULL,fecha_venta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,total DECIMAL(12,2) NOT NULL DEFAULT 0,estado ENUM('Pendiente','Pagada','Cancelada') NOT NULL DEFAULT 'Pendiente',CONSTRAINT fk_ventas_cliente_reportes FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)) ENGINE=InnoDB");
    $run("CREATE TABLE IF NOT EXISTS venta_detalle (id_detalle INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,id_venta INT UNSIGNED NOT NULL,codigo_producto INT NOT NULL,cantidad INT UNSIGNED NOT NULL,precio_unitario DECIMAL(12,2) NOT NULL,subtotal DECIMAL(12,2) NOT NULL,CONSTRAINT fk_detalle_venta_reportes FOREIGN KEY (id_venta) REFERENCES ventas(id_venta) ON DELETE CASCADE) ENGINE=InnoDB");
    $run('ALTER TABLE ventas ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL');

    return $log;
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['argv'][0] ?? '') === __FILE__) {
    try {
        $log = migrar();
        echo 'Migración OK (' . count($log) . " sentencias).\n";
    } catch (Throwable $e) {
        fwrite(STDERR, 'Error migrando: ' . $e->getMessage() . "\n");
        exit(1);
    }
}
