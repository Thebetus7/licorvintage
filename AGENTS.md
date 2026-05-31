# AGENTS.md — licorvintage

Laravel 12 + Jetstream 5.5 + Inertia.js + Vue 3 + Tailwind CSS
Liquor store management system (ventas, compras, stock, caja, promociones).

---

## Build / Dev / Lint / Test Commands

```bash
# Full project setup (install deps, key, migrate, build assets)
composer setup

# Development (runs server, queue, logs, vite concurrently)
composer dev
# or individually:
php artisan serve
npm run dev
npm run build            # production build (vite)

# Linting & Formatting
vendor/bin/pint           # PHP code style (Laravel Pint, PSR-12)
npm run lint              # if ESLint configured (currently none)
vendor/bin/phpstan        # static analysis (not installed yet)

# Testing
vendor/bin/phpunit                           # run all tests
vendor/bin/phpunit --testsuite=Unit           # unit tests only
vendor/bin/phpunit --testsuite=Feature        # feature tests only
vendor/bin/phpunit tests/Feature/ExampleTest.php  # single test file
vendor/bin/phpunit --filter test_the_application_returns_a_successful_response  # single test by name
composer test             # clears config + runs phpunit

# Database
php artisan migrate:fresh --seed   # reset & seed database
php artisan db:seed
```

---

## Project Structure

```
app/
  Actions/         # Fortify & Jetstream action classes
  Http/
    Controllers/   # Resource controllers (most are stubs)
    Middleware/
  Models/          # Eloquent models with relationships
  Providers/
database/
  migrations/      # migrations (PostgreSQL en desarrollo)
  seeders/
  factories/
resources/
  js/
    Pages/         # Inertia Vue pages (Dashboard, Welcome, Auth, Profile, API)
    Layouts/       # AppLayout.vue (main authenticated layout)
    Components/    # Reusable Vue components (Jetstream defaults)
    app.js         # Inertia + Ziggy bootstrap
  css/
  views/           # Blade templates (app.blade.php shell only)
routes/
  web.php          # Web routes (auth-protected, resource routes)
  api.php          # API routes (minimal, Sanctum auth)
config/            # Standard Laravel + Jetstream + Fortify configs
```

---

## Code Style Guidelines

### PHP (Backend)

- **Framework:** Laravel 12, PHP 8.2+
- **Formatter:** Laravel Pint (PSR-12 based, `vendor/bin/pint`)
- **Indentation:** 4 spaces (`.editorconfig`)
- **Imports:** `use` statements at top of file, one per line, grouped:
  1. PHP built-ins
  2. External packages (Illuminate, Inertia, etc.)
  3. App classes (`App\Models\*`, `App\Http\Controllers\*`)
- **Naming:** PascalCase for classes, camelCase for methods/variables, snake_case for DB columns/attributes
- **Models:**
  - Use `$fillable` for mass-assignment protection
  - Define `casts()` method (not `$casts` property)
  - Use `SoftDeletes` trait on all business models
  - Relationships: singular camelCase (`belongsTo`), plural camelCase (`hasMany`)
  - Return type hints on all relationship methods (`BelongsTo`, `HasMany`, etc.)
- **Controllers:**
  - Resource controllers (index, create, store, show, edit, update, destroy)
  - Use `Inertia::render('Page/View')` for responses
  - Auth via `auth()->user()` for scoped queries
  - Return Inertia responses, not JSON (unless API endpoint)
- **Migrations:**
  - Anonymous class syntax (`return new class extends Migration`)
  - `up()` / `down()` methods with `: void` return types
  - Use `$table->softDeletes()` on business tables
  - Use `$table->foreignId('model_id')` for foreign keys
- **Error handling:** Exceptions propagate; use `abort()` or validation exceptions
- **No comments** in code unless explicitly requested

### Vue / JavaScript (Frontend)

- **Framework:** Vue 3 with Composition API (`<script setup>`)
- **Router:** Inertia.js v2 (`@inertiajs/vue3`)
- **Styling:** Tailwind CSS 4 with `@tailwindcss/forms` and `@tailwindcss/typography` plugins
- **Path alias:** `@/*` maps to `resources/js/*` (jsconfig.json)
- **Components:**
  - PascalCase filenames: `AppLayout.vue`, `NavLink.vue`
  - Use `<script setup>` with `defineProps()` (no Options API)
  - Import from `@inertiajs/vue3`: `Head`, `Link`, `router`
  - Use Ziggy `route()` helper for named routes
- **Layouts:** Wrap pages in `AppLayout.vue` via `<AppLayout title="Page Title">`
- **Props:** Use `defineProps()` with type definitions
- **Formatting:** 4-space indent, LF line endings, trailing commas

---

## Database Schema (Key Tables)

| Table | Key Fields | Notes |
|---|---|---|
| `productos` | nombre, mililitros, codigo_barra (unique), imagen | Core product |
| `stocks` | stock, min, max, producto_id | Inventory per product |
| `ventas` | monto_pagado, monto_original, monto_final, tipo_pago, nro_cuotas, user_id | Sales |
| `detalle_ventas` | cantidad, precio_original, descuento, precio_u_final, subtotal, venta_id, producto_id | Sale line items |
| `compras` | costo, producto_id, user_id | Purchases |
| `detalle_compras` | cantidad, sub_costo, compra_id, producto_id | Purchase line items |
| `proveedors` | nombre, telefono, descripcion | Suppliers |
| `apertura_cajas` | monto_inicial, monto_sistema, monto_real, diferencia, estado, user_id | Cash register |
| `movimiento_cajas` | monto, tipo, detalle, apertura_caja_id | Cash movements |
| `promocions` | nombre_promo, codigo_promo, descuento, tipo_descuento, fecha_inicio, fecha_fin | Promotions |
| `detalle_promos` | producto_id, promocion_id | Product-promotion link |
| `venta_cuotas` | sub_monto, venta_id | Installment payments |
| `metodo_pagos` | tipo_pago, venta_id | Payment methods |

All business tables use `softDeletes` and `timestamps`.

---

## Key Conventions

1. **Routes:** Business routes go inside the `auth:sanctum` + `verified` middleware group in `web.php`. Use `Route::resource()` for CRUD.
2. **Controller pattern:** Controllers use `auth()->user()` to scope data. Use model static methods (e.g., `Compra::allCompra($userId)`) for complex queries.
3. **Page components:** Each Inertia page lives in `resources/js/Pages/{Entity}/{Action}.vue`. Use `AppLayout` wrapper.
4. **Navigation:** Add nav links in `AppLayout.vue` sidebar using `<NavLink>` component with Ziggy `route()`.
5. **Testing:** Tests use `TestCase` base class, in-memory SQLite (`:memory:`) via `phpunit.xml`, and `RefreshDatabase` trait for feature tests. Desarrollo local usa **PostgreSQL** (`DB_CONNECTION=pgsql`).
6. **No API controllers** for business logic — everything uses Inertia web routes.
7. **Blueprint** (`draft.yaml`) is the source of truth for the data model schema.

---

## Current State

- **Completed:** Database migrations, Eloquent models with relationships, Jetstream auth scaffold, `CompraController` (partial)
- **In Progress:** Resource controllers (most are empty stubs), Vue pages for business CRUD
- **Missing:** Frontend pages for Productos, Ventas, Stock, Caja, Promociones, Proveedores; controller logic for all resources except Compra
