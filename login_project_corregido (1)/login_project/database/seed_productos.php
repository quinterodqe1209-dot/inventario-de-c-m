<?php
// database/seed_productos.php — Siembra 20 productos demo (idempotente: omite los que ya existen por nombre).
// Uso: & "C:\xampp\php\php.exe" database\seed_productos.php   (desde login_project/)

require_once __DIR__ . '/../config/conexion.php';

$productos = [
    // [nombre(<=40), descripcion, marca, precio, stock, minimo, proveedor, categoria]
    ['Disco corte 4.5" inox 1mm', 'Disco de corte para acero inoxidable, 115x1.0mm.', 'Norton', 12500, 120, 30, 'Norton Saint-Gobain', 'DISCOS DE CORTE'],
    ['Disco corte 7" acero 1.6mm', 'Disco de corte para acero al carbono, 180mm.', 'Dewalt', 18900, 85, 20, 'Dewalt Colombia', 'DISCOS DE CORTE'],
    ['Disco corte 9" industrial 2mm', 'Disco de corte industrial para metal, 230mm.', 'Bosch', 26400, 60, 15, 'Bosch Colombia', 'DISCOS DE CORTE'],
    ['Disco diamantado 4.5" concreto', 'Disco diamantado segmentado para concreto.', 'Tyrolit', 45900, 40, 10, 'Tyrolit Andina', 'DISCOS DE CORTE'],
    ['Disco diamantado 7" porcelanato', 'Disco diamantado continuo para porcelanato.', '3M', 78900, 8, 12, '3M Colombia', 'DISCOS DE CORTE'],
    ['Disco desbaste 4.5" metal', 'Disco de desbaste para soldadura y metal.', 'Norton', 13900, 150, 40, 'Norton Saint-Gobain', 'DESBASTE Y PULIDO'],
    ['Disco flap 4.5" grano 60', 'Disco flap zirconio grano 60 para desbaste.', 'Klingspor', 15800, 200, 50, 'Klingspor Colombia', 'DESBASTE Y PULIDO'],
    ['Disco flap 4.5" grano 120', 'Disco flap zirconio grano 120 para acabado.', 'Klingspor', 16200, 0, 25, 'Klingspor Colombia', 'DESBASTE Y PULIDO'],
    ['Copa diamantada 4.5" pulido', 'Copa diamantada turbo para pulir concreto.', 'Tyrolit', 92500, 18, 8, 'Tyrolit Andina', 'DESBASTE Y PULIDO'],
    ['Disco fibra 5" grano 36 Cubitron', 'Disco de fibra Cubitron II para metal.', '3M', 21400, 90, 30, '3M Colombia', 'DESBASTE Y PULIDO'],
    ['Lija agua #220 pliego 9x11', 'Lija al agua grano 220, pliego 9x11 pulg.', '3M', 3200, 500, 100, '3M Colombia', 'LIJAS Y BANDAS'],
    ['Lija agua #400 pliego 9x11', 'Lija al agua grano 400 para acabado fino.', '3M', 3400, 450, 100, '3M Colombia', 'LIJAS Y BANDAS'],
    ['Banda lija 75x533mm grano 80', 'Banda de lija para lijadora de banda.', 'Norton', 18900, 70, 20, 'Norton Saint-Gobain', 'LIJAS Y BANDAS'],
    ['Disco velcro 5" grano 100 x5und', 'Set x5 discos velcro para lijadora orbital.', 'Bosch', 24500, 110, 25, 'Bosch Colombia', 'LIJAS Y BANDAS'],
    ['Rollo lija tela #60 10m', 'Rollo de lija en tela grano 60 por 10 metros.', 'Klingspor', 58900, 6, 10, 'Klingspor Colombia', 'LIJAS Y BANDAS'],
    ['Grata circular 4.5" alambre', 'Grata circular de alambre para pulidora.', 'Dewalt', 27900, 65, 15, 'Dewalt Colombia', 'GRATAS Y CEPILLOS'],
    ['Cepillo copa 65mm alambre trenzado', 'Cepillo copa de alambre trenzado 65mm.', 'Bosch', 32500, 48, 12, 'Bosch Colombia', 'GRATAS Y CEPILLOS'],
    ['Grata manual mango plástico', 'Grata manual con mango plástico, 4 hileras.', 'C&M', 8900, 300, 60, 'Proveedor por asignar', 'GRATAS Y CEPILLOS'],
    ['Pulidora angular 4.5" 900W', 'Pulidora angular profesional 900W 11000rpm.', 'Dewalt', 289000, 12, 5, 'Dewalt Colombia', 'MAQUINARIA Y KITS'],
    ['Kit 10 discos corte+desbaste 4.5"', 'Kit surtido x10 discos de corte y desbaste.', 'C&M', 99000, 35, 10, 'Proveedor por asignar', 'MAQUINARIA Y KITS'],
];

try {
    $db = (new Conexion())->conn;
    // Limpia el mojibake heredado de la categoría semilla.
    $db->exec("UPDATE categoria SET descripcion = 'Categoría de productos' WHERE descripcion <> 'Categoría de productos'");

    $catIds = [];
    $catSel = $db->prepare('SELECT id_categoria FROM categoria WHERE nombre_categoria = ? LIMIT 1');
    $catIns = $db->prepare('INSERT INTO categoria (nombre_categoria, descripcion) VALUES (?, ?)');
    $existe = $db->prepare('SELECT COUNT(*) FROM productos WHERE PRO_nombre_producto = ? AND deleted_at IS NULL');
    $prodIns = $db->prepare('INSERT INTO productos (PRO_codigo, PRO_nombre_producto, PRO_descripcion, PRO_marca, PRO_imagen_url, PRO_proveedor, PRO_precio_unitario, PRO_stock_actual, PRO_stock_minimo, PRO_stock_maximo, PRO_cantidad_disponible, id_categoria, id_tipo_material, PRO_costo_base) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $catProd = $db->prepare('INSERT INTO catalogo (CAT_id_producto, GER_documento_identidad, CAT_nombre_producto, CAT_tipo_material, CAT_unidad_empaque, CAT_medidas, CAT_precio_unitario, CAT_foto_empaque) VALUES (?,?,?,?,?,?,?,?)');

    $db->beginTransaction();
    $nuevos = 0;
    $omitidos = 0;
    foreach ($productos as [$nombre, $desc, $marca, $precio, $stock, $minimo, $proveedor, $categoria]) {
        $existe->execute([$nombre]);
        if ((int) $existe->fetchColumn() > 0) {
            $omitidos++;
            continue;
        }
        if (!isset($catIds[$categoria])) {
            $catSel->execute([$categoria]);
            $id = $catSel->fetchColumn();
            if (!$id) {
                $catIns->execute([$categoria, 'Categoría de productos abrasivos']);
                $id = $db->lastInsertId();
            }
            $catIds[$categoria] = $id;
        }
        $codigo = (int) $db->query('SELECT COALESCE(MAX(PRO_codigo),0)+1 FROM productos')->fetchColumn();
        $costo = round($precio * 0.7, 2);
        $prodIns->execute([$codigo, $nombre, $desc, $marca, null, $proveedor, $precio, $stock, $minimo, $minimo * 2, $stock, $catIds[$categoria], 1, $costo]);
        $catProd->execute([$codigo, 2, $nombre, 'Abrasivo', 'Unidad', '', $precio, null]);
        $nuevos++;
    }
    $db->commit();
    echo "Seed OK: $nuevos nuevos, $omitidos ya existían.\n";
    echo 'Total productos activos: ' . $db->query('SELECT COUNT(*) FROM productos WHERE deleted_at IS NULL')->fetchColumn() . "\n";
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, 'Seed ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}
