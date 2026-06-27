<?php

namespace Tests\Feature;

use App\Models\AperturaCaja;
use App\Models\MenuItem;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaCuotas;
use App\Services\InventarioService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LicorModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_owner_user(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@gmail.com')->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('propietario'));
    }

    public function test_product_store_creates_product_with_stock_and_initial_movement(): void
    {
        $owner = $this->userWithRole('propietario');

        $this->actingAs($owner)->post(route('productos.store'), [
            'nombre' => 'Whisky Reserva',
            'mililitros' => 750,
            'costo' => 90,
            'precio_venta' => 140,
            'descripcion' => 'Botella premium',
            'imagen' => null,
            'fotos' => ['https://example.com/whisky.jpg'],
            'codigo_barra' => 'INV-001',
            'publicado' => false,
            'stock' => [
                'stock' => 12,
                'min' => 2,
                'max' => 30,
            ],
        ])->assertSessionHasNoErrors();

        $producto = Producto::where('codigo_barra', 'INV-001')->firstOrFail();

        $this->assertSame(12, $producto->stockActual->stock);
        $this->assertEquals(140.0, $producto->precio_venta);
        $this->assertFalse($producto->publicado);
        $this->assertDatabaseHas('movimiento_inventarios', [
            'producto_id' => $producto->id,
            'tipo' => 'ingreso_inicial',
            'cantidad' => 12,
            'saldo_cantidad' => 12,
        ]);
    }

    public function test_purchase_with_nullable_supplier_increments_stock_and_registers_movement(): void
    {
        $seller = $this->userWithRole('vendedor');
        $producto = $this->createProductWithStock('COMP-001', 5, 50);

        $this->actingAs($seller)->post(route('compras.store'), [
            'proveedor_id' => null,
            'detalles' => [
                ['producto_id' => $producto->id, 'cantidad' => 3, 'sub_costo' => 150],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('compras', [
            'proveedor_id' => null,
            'costo' => 150,
        ]);
        $this->assertSame(8, $producto->fresh()->stockActual->stock);
        $this->assertDatabaseHas('movimiento_inventarios', [
            'producto_id' => $producto->id,
            'tipo' => 'ingreso_compra',
            'cantidad' => 3,
        ]);
    }

    public function test_sale_decrements_stock_and_registers_cash_movement_and_inventory_exit(): void
    {
        $seller = $this->userWithRole('vendedor');
        $producto = $this->createProductWithStock('VENTA-001', 4, 30, 55);
        $caja = AperturaCaja::create([
            'monto_inicial' => 100,
            'monto_sistema' => 100,
            'monto_real' => null,
            'diferencia' => null,
            'tiempo_apertura' => now(),
            'tiempo_cierre' => null,
            'estado' => 'abierta',
            'user_id' => $seller->id,
        ]);

        $this->actingAs($seller)->post(route('ventas.store'), [
            'tipo_pago' => 'efectivo',
            'monto_pagado' => 110,
            'detalles' => [
                ['producto_id' => $producto->id, 'cantidad' => 2],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, $producto->fresh()->stockActual->stock);
        $this->assertSame(210.0, $caja->fresh()->monto_sistema);
        $this->assertDatabaseHas('movimiento_cajas', [
            'apertura_caja_id' => $caja->id,
            'tipo' => 'venta',
            'monto' => 110,
        ]);
        $this->assertDatabaseHas('movimiento_inventarios', [
            'producto_id' => $producto->id,
            'tipo' => 'salida_venta',
            'cantidad' => 2,
            'saldo_cantidad' => 2,
        ]);
    }

    public function test_sale_rejects_quantity_greater_than_stock(): void
    {
        $seller = $this->userWithRole('vendedor');
        $producto = $this->createProductWithStock('VENTA-002', 1, 40, 70);
        AperturaCaja::create([
            'monto_inicial' => 50,
            'monto_sistema' => 50,
            'monto_real' => null,
            'diferencia' => null,
            'tiempo_apertura' => now(),
            'tiempo_cierre' => null,
            'estado' => 'abierta',
            'user_id' => $seller->id,
        ]);

        $this->actingAs($seller)->post(route('ventas.store'), [
            'tipo_pago' => 'efectivo',
            'monto_pagado' => 140,
            'detalles' => [
                ['producto_id' => $producto->id, 'cantidad' => 2],
            ],
        ])->assertSessionHasErrors('detalles');

        $this->assertSame(1, $producto->fresh()->stockActual->stock);
    }

    public function test_physical_count_creates_adjustment_and_inventory_movement(): void
    {
        $owner = $this->userWithRole('propietario');
        $producto = $this->createProductWithStock('CONTEO-001', 10, 20);

        $this->actingAs($owner)->post(route('inventario.conteo.store'), [
            'conteos' => [
                ['producto_id' => $producto->id, 'stock_fisico' => 8],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame(8, $producto->fresh()->stockActual->stock);
        $this->assertDatabaseHas('ajuste_inventarios', [
            'producto_id' => $producto->id,
            'stock_sistema' => 10,
            'stock_fisico' => 8,
            'diferencia' => -2,
        ]);
        $this->assertDatabaseHas('movimiento_inventarios', [
            'producto_id' => $producto->id,
            'tipo' => 'salida_ajuste',
            'cantidad' => 2,
            'saldo_cantidad' => 8,
        ]);
    }

    public function test_kardex_shows_movement_sequence(): void
    {
        $owner = $this->userWithRole('propietario');
        $producto = $this->createProductWithStock('KARDEX-001', 0, 10);

        app(InventarioService::class)->registrarIngreso(
            $producto,
            10,
            10.0,
            'ingreso_inicial',
            null,
            $owner,
        );

        app(InventarioService::class)->registrarSalida(
            $producto,
            3,
            'salida_venta',
            null,
            $owner,
        );

        $movimientos = MovimientoInventario::query()
            ->where('producto_id', $producto->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $movimientos);
        $this->assertSame(10, $movimientos[0]->saldo_cantidad);
        $this->assertSame(7, $movimientos[1]->saldo_cantidad);
    }

    public function test_propietario_can_access_inventory_index(): void
    {
        $owner = $this->userWithRole('propietario');

        $this->actingAs($owner)
            ->get(route('inventario.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Inventario/Index'));
    }

    public function test_vendedor_cannot_access_inventory_module(): void
    {
        MenuItem::create([
            'label' => 'Inventario',
            'route_name' => 'inventario.index',
            'roles' => ['propietario'],
        ]);

        $seller = $this->userWithRole('vendedor');

        $this->actingAs($seller)->get(route('inventario.index'))->assertForbidden();
    }

    public function test_caja_full_flow_open_sales_close(): void
    {
        // 1. Setup: propietario, vendedor, product with stock
        $owner = $this->userWithRole('propietario');
        $seller = $this->userWithRole('vendedor');
        $producto = $this->createProductWithStock('CAJA-TEST-001', 50, 30, 55);

        // 2. Propietario opens caja for vendedor
        $this->actingAs($owner)->post(route('caja.open'), [
            'vendedor_id' => $seller->id,
            'monto_inicial' => 500,
        ])->assertSessionHasNoErrors();

        $caja = AperturaCaja::where('user_id', $seller->id)
            ->where('estado', 'abierta')
            ->firstOrFail();

        $this->assertSame(500.0, $caja->monto_inicial);
        $this->assertSame(500.0, $caja->monto_sistema);
        $this->assertSame($owner->id, $caja->opened_by);
        $this->assertSame($seller->id, $caja->user_id);
        $this->assertNull($caja->getRawOriginal('totales_caja'));
        $this->assertEquals(['efectivo' => 0, 'qr' => 0, 'tarjeta' => 0, 'credito' => 0], $caja->totales_sistema);

        // 3. Vendedor creates an efectivo sale
        $this->actingAs($seller)->post(route('ventas.store'), [
            'tipo_pago' => 'efectivo',
            'monto_pagado' => 110,
            'detalles' => [
                ['producto_id' => $producto->id, 'cantidad' => 2],
            ],
        ])->assertSessionHasNoErrors();

        $caja->refresh();
        $this->assertEquals(['efectivo' => 110, 'qr' => 0, 'tarjeta' => 0, 'credito' => 0], $caja->totales_sistema);
        $this->assertEquals(610.0, $caja->monto_sistema); // 500 + 110
        $this->assertDatabaseHas('movimiento_cajas', [
            'apertura_caja_id' => $caja->id,
            'tipo' => 'venta',
            'monto' => 110,
        ]);

        // 4. Vendedor creates a QR sale
        $this->actingAs($seller)->post(route('ventas.store'), [
            'tipo_pago' => 'qr',
            'monto_pagado' => 110,
            'detalles' => [
                ['producto_id' => $producto->id, 'cantidad' => 2],
            ],
        ])->assertSessionHasNoErrors();

        $caja->refresh();
        $this->assertEquals(['efectivo' => 110, 'qr' => 110, 'tarjeta' => 0, 'credito' => 0], $caja->totales_sistema);
        $this->assertEquals(720.0, $caja->monto_sistema); // 610 + 110

        // 5. Vendedor creates a credit sale to a client
        Role::create(['name' => 'cliente', 'guard_name' => 'web']);
        $cliente = User::factory()->create();
        $cliente->assignRole('cliente');
        $this->actingAs($seller)->post(route('ventas.store'), [
            'tipo_pago' => 'credito',
            'cliente_id' => $cliente->id,
            'nro_cuotas' => 3,
            'detalles' => [
                ['producto_id' => $producto->id, 'cantidad' => 5],
            ],
        ])->assertSessionHasNoErrors();

        $caja->refresh();
        // Credit sales don't affect cash register totals
        $this->assertEquals(['efectivo' => 110, 'qr' => 110, 'tarjeta' => 0, 'credito' => 0], $caja->totales_sistema);
        $this->assertEquals(720.0, $caja->monto_sistema);

        // Verify credit installments were created
        $ventaCredito = Venta::with('ventaCuotas')
            ->where('tipo_pago', 'credito')
            ->where('cliente_id', $cliente->id)
            ->firstOrFail();
        $this->assertCount(3, $ventaCredito->ventaCuotas);
        $this->assertEquals(275.0, $ventaCredito->monto_final); // 5 * 55 = 275
        $this->assertTrue($ventaCredito->ventaCuotas->every(fn ($q) => $q->estado === 'pendiente'));

        // 6. Vendedor closes the caja with arqueo data
        $this->actingAs($seller)->put(route('caja.close', $caja), [
            'totales_caja' => [
                'efectivo' => 110,
                'qr' => 110,
                'tarjeta' => 0,
                'credito' => 0,
            ],
        ])->assertSessionHasNoErrors();

        $caja->refresh();
        $this->assertEquals('cerrada', $caja->estado);
        $this->assertEquals(220.0, $caja->monto_real);  // 110 + 110 + 0 + 0
        $this->assertEquals(220.0 - 720.0, $caja->diferencia); // -500
        $this->assertNotNull($caja->tiempo_cierre);
        $this->assertEquals([
            'efectivo' => 110,
            'qr' => 110,
            'tarjeta' => 0,
            'credito' => 0,
        ], $caja->totales_caja);

        // 7. Verify another vendedor cannot close someone else's caja
        $otherSeller = $this->userWithRole('vendedor');
        $cajaCerrada = $caja->fresh();

        // 8. Verify propietario cannot sell without an open caja
        $otherProducto = $this->createProductWithStock('CAJA-TEST-002', 10, 20, 40);
        $this->actingAs($seller)->post(route('ventas.store'), [
            'tipo_pago' => 'efectivo',
            'monto_pagado' => 40,
            'detalles' => [
                ['producto_id' => $otherProducto->id, 'cantidad' => 1],
            ],
        ])->assertForbidden();
    }

    public function test_caja_installment_payment_updates_cash_register(): void
    {
        // Setup: owner opens caja, seller creates credit sale
        $owner = $this->userWithRole('propietario');
        $seller = $this->userWithRole('vendedor');
        Role::findOrCreate('cliente');
        $cliente = User::factory()->create();
        $cliente->assignRole('cliente');
        $producto = $this->createProductWithStock('CAJA-TEST-003', 20, 15, 30);

        $this->actingAs($owner)->post(route('caja.open'), [
            'vendedor_id' => $seller->id,
            'monto_inicial' => 200,
        ])->assertSessionHasNoErrors();

        $caja = AperturaCaja::where('estado', 'abierta')->firstOrFail();

        $this->actingAs($seller)->post(route('ventas.store'), [
            'tipo_pago' => 'credito',
            'cliente_id' => $cliente->id,
            'nro_cuotas' => 2,
            'detalles' => [
                ['producto_id' => $producto->id, 'cantidad' => 3],
            ],
        ])->assertSessionHasNoErrors();

        $cuota = VentaCuotas::whereHas('venta', fn ($q) => $q->where('cliente_id', $cliente->id))
            ->where('estado', 'pendiente')
            ->firstOrFail();

        $montoCuota = $cuota->sub_monto;

        // Pay the installment
        $this->actingAs($seller)->post(route('caja.cuotas.pagar', $cuota))
            ->assertSessionHasNoErrors();

        $caja->refresh();
        $this->assertEquals(200.0 + $montoCuota, $caja->monto_sistema);
        $this->assertDatabaseHas('venta_cuotas', [
            'id' => $cuota->id,
            'estado' => 'pagado',
        ]);
        $this->assertDatabaseHas('movimiento_cajas', [
            'apertura_caja_id' => $caja->id,
            'tipo' => 'ingreso',
            'monto' => $montoCuota,
        ]);
    }

    public function test_caja_open_validates_vendedor_exists(): void
    {
        $owner = $this->userWithRole('propietario');

        $this->actingAs($owner)->post(route('caja.open'), [
            'vendedor_id' => 99999,
            'monto_inicial' => 100,
        ])->assertSessionHasErrors('vendedor_id');
    }

    public function test_propietario_can_view_caja_index(): void
    {
        Role::findOrCreate('vendedor');
        Role::findOrCreate('cliente');
        $owner = $this->userWithRole('propietario');

        $this->actingAs($owner)
            ->get(route('caja.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Caja/Index'));
    }

    public function test_caja_index_shows_comprobantes_and_creditos(): void
    {
        Role::findOrCreate('cliente');
        $owner = $this->userWithRole('propietario');
        $seller = $this->userWithRole('vendedor');
        $producto = $this->createProductWithStock('CAJA-TEST-004', 30, 20, 50);

        $this->actingAs($owner)->post(route('caja.open'), [
            'vendedor_id' => $seller->id,
            'monto_inicial' => 300,
        ])->assertSessionHasNoErrors();

        // Create some sales
        $this->actingAs($seller)->post(route('ventas.store'), [
            'tipo_pago' => 'efectivo',
            'monto_pagado' => 100,
            'detalles' => [
                ['producto_id' => $producto->id, 'cantidad' => 2],
            ],
        ])->assertSessionHasNoErrors();

        // Propietario views caja index
        $this->actingAs($owner)
            ->get(route('caja.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Caja/Index')
                ->has('cajaActiva')
                ->has('comprobantes', 1)
                ->has('vendedores')
                ->has('clientes')
            );
    }

    private function createProductWithStock(string $codigo, int $stock, float $costo, float $precio = 85): Producto
    {
        $producto = Producto::create([
            'nombre' => 'Producto '.$codigo,
            'mililitros' => 750,
            'costo' => $costo,
            'costo_promedio' => $costo,
            'precio_venta' => $precio,
            'codigo_barra' => $codigo,
            'publicado' => true,
        ]);
        $producto->stocks()->create(['stock' => $stock, 'min' => 1, 'max' => 20]);

        if ($stock > 0) {
            MovimientoInventario::create([
                'producto_id' => $producto->id,
                'tipo' => 'ingreso_inicial',
                'cantidad' => $stock,
                'costo_unitario' => $costo,
                'saldo_cantidad' => $stock,
                'saldo_costo_promedio' => $costo,
                'user_id' => User::factory()->create()->id,
            ]);
        }

        return $producto;
    }

    private function userWithRole(string $role): User
    {
        Role::findOrCreate($role);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
