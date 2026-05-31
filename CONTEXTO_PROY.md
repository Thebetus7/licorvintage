# Contexto General del Proyecto

**Licor Vintage** es un sistema Laravel 12 + Jetstream + Inertia + Vue 3 para una **licorería** que opera como una **tienda online** habitual (catálogo público, carrito, checkout) y, en paralelo, como **punto de venta** interno (caja, inventario, contabilidad). El proyecto debe cerrar exitosamente implementando los **casos de uso aprobados** abajo, con validación y lógica crítica siempre en backend.

## Visión del producto

- Experiencia **ecommerce** para el **cliente**: al entrar a la web, lo primero que ve es el **catálogo de productos en venta** (con stock), no el panel administrativo.
- Experiencia **operativa** para **propietario** y **vendedor**: inventario, caja, compras, promociones, reportes y gestión de usuarios.
- Misma base de datos de productos, stock, precios y promociones alimenta tanto la venta online como la venta en mostrador/caja.

## Roles

| Rol | Descripción |
|-----|-------------|
| **propietario** (admin) | Acceso total: usuarios, contabilidad/caja, inventario, promociones, reportes, configuración. |
| **vendedor** (cajero) | Opera en tienda: ventas en caja, arqueo según permisos, inventario/compras según política del propietario. No gestiona usuarios ni configuración global. |
| **cliente** | Registro público (incl. **Social SSO**). Navega catálogo, compra online, métodos de pago habilitados. Perfil con **foto** opcional. |

## Casos de uso (aprobados)

### CU1. Gestión de usuarios

- Propietario, vendedor y clientes.
- Clientes: registro/login, **Social SSO**, **fotos** de perfil.
- Propietario: alta/edición/baja de vendedores y políticas de acceso (Spatie Permission).

### CU2. Gestión de productos (catálogo)

- CRUD de productos para la licorería.
- **Fotos** de producto, **código de barras / QR** para identificación y consulta.
- Catálogo visible al cliente (solo productos con stock y publicados según reglas de negocio).

### CU3. Gestión contable (caja)

- **Abrir** caja (monto inicial).
- **Arqueo** / movimientos durante el turno.
- **Cerrar** caja (monto real vs sistema, diferencia).

### CU4. Gestión de inventarios

- **Ingresos** (compras, ajustes positivos).
- **Salidas** (ventas, mermas, ajustes negativos).
- **Técnica de inventario** (conteo, reconciliación stock físico vs sistema).
- **Técnicas de costo** (costo promedio u otra definida en negocio; coherente con compras y valorización).

### CU5. Gestión de promociones

- Por **producto(s)**, **porcentaje** (u otro tipo de descuento acordado).
- **Fecha inicio** y **fecha fin**.
- Aplicación automática en ventas (caja y ecommerce) dentro del vigente.

### CU6. Gestión de ventas

- **Contado**: pago inmediato.
- **Crédito**: **2 o más cuotas** (plan de cuotas vinculado a la venta).
- Canal **mostrador** (vendedor/caja) y canal **online** (cliente).

### CU7. Gestión de pagos

- Métodos: **QR**, **tarjeta**, **efectivo** (y registro en venta / caja según corresponda).
- Integración con flujo de venta contado y, donde aplique, registro de pagos de cuotas.

### CU8. Reportes y estadísticas

- Ventas, stock, caja, promociones, compras, etc.
- Dashboards o listados exportables según prioridad del sprint.

## Flujo cliente (ecommerce)

1. Landing / home → **lista de productos** (imagen, nombre, precio, promoción si aplica).
2. Detalle de producto → cantidad limitada por **stock** disponible.
3. Carrito → checkout → selección de **tipo de pago** y método (CU7).
4. Confirmación de pedido/venta; actualización de **stock** (salida de inventario, CU4).

## Flujo operativo (tienda)

1. **Vendedor** abre turno de **caja** (CU3), registra ventas contado o crédito con cuotas (CU6).
2. **Propietario** supervisa usuarios (CU1), inventario y costos (CU4), promociones (CU5) y reportes (CU8).
3. **Compras / proveedores** alimentan ingresos de inventario (CU4).

## Módulos actuales en código (referencia)

- **Inventario**: productos, costo, precio de venta, stock.
- **Compras**: varios productos, proveedor opcional; incrementa stock.
- **Proveedores**: CRUD (módulo compras).
- **Caja**: abrir / cerrar, ventas, movimientos.
- **Usuarios**: propietario gestiona vendedores.
- **Cliente**: catálogo público; cantidad acotada al stock.

## Convenciones técnicas

- Controladores delgados; respuestas **Inertia** (web), no JSON salvo API explícita.
- Validación en **FormRequest**.
- Operaciones multi-tabla en **services** con `DB::transaction()`.
- Autorización: **Spatie Laravel Permission** por roles.
- CRUD con **flash messages** compartidos por Inertia.
- Stock, caja, promociones y pagos: **siempre validados en backend**; Vue solo mejora UX.

## Criterio de éxito del proyecto

Entregar una licorería usable como **tienda online** (cliente ve productos al entrar y puede comprar) y como **negocio administrado** (propietario + cajero), con los **8 casos de uso** implementados de forma coherente en modelo de datos, rutas, UI y reglas de negocio.
