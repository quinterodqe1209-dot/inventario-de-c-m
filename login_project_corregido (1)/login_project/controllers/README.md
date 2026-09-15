# MVC — Estado actual (refactorizado sin romper funcionalidad)

`index.php` es solo el **Controlador Frontal**: bootstrap de sesión/CSRF + delegación + enrutado.
No contiene SQL ni reglas de negocio.

## Controladores (toda la lógica de petición)

- `AuthController.php`: `login`, `logout`, `register`, `enviar_codigo_verificacion`, `recuperar`, `restablecer`, `validarToken`.
- `UsuarioController.php`: listado/admin (`eliminar_usuario`), `crear_usuario`, `actualizar_usuario`, perfil (`actualizar_perfil`, `cambiar_password`).
- `ClienteController.php` (nuevo): portal cliente — `toggle_favorite`, `crear_pqrs`, catálogo, facturas y KPIs.
- `InventarioController.php`: `product_action=create/update/delete` + búsqueda `?q`.
- `ReporteController.php` (nuevo): `sale_action=create/update/delete`, filtros `?desde/?hasta`, `?export=csv/pdf`.
- `PagoController.php`: `enviar_comprobante_pago`, `actualizar_comprobante_pago`.
- `ProveedorController.php`: `generar_orden_reabastecimiento`, `actualizar_orden`, `agendar_entrega`, `subir_factura`.
- `PqrsController.php`: `actualizar_estado_pqrs` + listado admin.
- `DashboardController.php`: `dashboard_data` (JSON) + datos gerente.

## Modelos (toda la SQL)

- `usuario.php` + `UsuarioModel.php`: usuarios y perfil.
- `ProductoModel.php`: productos / categoria / catalogo.
- `VentaModel.php` (+ `ClienteModel`): clientes, ventas, detalle.
- `ComprobantePagoModel.php`: comprobantes cliente/gerente.
- `OrdenCompraModel.php`: orden_compra, detalle, despacho_bodega, factura_orden_compra.
- `PasswordResetModel.php`: password_resets.
- `PqrsModel.php`: pqrs (admin + cliente).
- `ClientePortalModel.php`: favoritos, perfil_cliente, factura_pedido/pedido.
- `DashboardModel.php`: indicadores.

## Vistas (solo presentación)

Reciben `$data` del controlador vía `core/View.php::view()` (marca `$__MVC_READY`).
Conservan su bloque legacy envuelto en `if (empty($__MVC_READY))` para acceso directo,
con los mismos nombres de variables, formularios (`action`, `product_action`, `sale_action`),
tablas SQL y mensajes. Nada visual ni funcional se eliminó.

## Mejoras aplicadas (2ª vuelta, sin romper nada)

- **Proveedores conectado a BD**: `views/proveedores_dashboard.php` ahora exige login/rol,
  muestra panel real (stock bajo con botón "Generar OC real", últimas OC) con los mismos
  POST/CSRF existentes, inyecta `window.CM_REAL` e hidrata el React mock sin borrarlo.
  Nuevo API: `index.php?action=proveedor_data` (JSON).
- **Migraciones centrales**: `database/migrate.php::migrar()` idempotente.
  Ejecutar con `php database/migrate.php` o `index.php?action=migrar` (gerente/admin).
  Los `ensure*` de modelos llevan guard estático (1 vez por request).
- **Roles unificados**: `admin` = `gerente` en todos los controladores, router y vistas.
- **Seguridad**: `conexion.php` sin filtrar detalles + `charset=utf8mb4` + prepares reales;
  `uploads/.htaccess` niega ejecución PHP; registro exige mínimo 6 caracteres;
  cabeceras `nosniff/SAMEORIGIN/Referrer-Policy` en `index.php`.
- **Layout**: `views/partials/flash.php` reutilizable para mensajes.

## Core

- `core/View.php`: `view()`, `redirect()`, `json_response()`.
- `core/Controller.php`: `BaseController` (db, rol, permisos, CSRF).

## Regla

Se conservan intactos: `?action`, campos POST/GET, tablas y textos de mensajes.
`index.php` delega; jamás implementa.
Copia de seguridad del frontal anterior: `index.legacy.php`.
