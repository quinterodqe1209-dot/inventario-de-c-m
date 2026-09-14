# Controladores

Este directorio contiene la logica que recibe peticiones y coordina modelos y vistas.

## Controlador actual

- `UsuarioController.php`: login y registro de usuarios. Usa `models/usuario.php`.

## Acciones que aun coordina `index.php`

`index.php` sigue siendo el controlador frontal y conserva temporalmente estas acciones para no romper los formularios existentes:

| Accion POST/GET | Controlador responsable recomendado | Vista principal |
| --- | --- | --- |
| `login`, `register`, `enviar_codigo_verificacion` | `UsuarioController.php` | `views/login.php`, `views/register.php` |
| `recuperar`, `restablecer` | `AuthController.php` | `views/recuperar.php`, `views/restablecer.php` |
| `enviar_comprobante_pago`, `actualizar_comprobante_pago` | `PagoController.php` | `views/pago_seguro.php`, `views/gerente_sbadm.php` |
| `generar_orden_reabastecimiento`, `actualizar_orden`, `agendar_entrega`, `subir_factura` | `ProveedorController.php` | `views/proveedores_dashboard.php` |
| `actualizar_estado_pqrs` | `PqrsController.php` | `views/pqrs_admin.php` |
| `dashboard_data` | `DashboardController.php` | `views/gerente_sbadm.php`, `views/inventario.php` |
| `logout` | `AuthController.php` | `views/login.php` |

## Regla para la siguiente etapa

Cada controlador nuevo debe conservar exactamente los nombres actuales de las acciones, variables POST/GET y tablas SQL. `index.php` debe quedarse como punto de entrada y delegar la accion al controlador correspondiente.

No se crearon clases vacias: mientras una accion permanezca en `index.php`, ese archivo sigue siendo su implementacion real. Esta separacion documenta el destino correcto para extraerla sin romper el sistema.
