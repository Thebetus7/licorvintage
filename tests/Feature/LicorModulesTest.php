<?php

namespace Tests\Feature;

use App\Models\AperturaCaja;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
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
        $this->assertSame(140.0, $producto->precio_venta);
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

        app(\App\Services\InventarioService::class)->registrarIngreso(
            $producto,
            10,
            10.0,
            'ingreso_inicial',
            null,
            $owner,
        );

        app(\App\Services\InventarioService::class)->registrarSalida(
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

    public function test_vendedor_cannot_access_inventory_module(): void
    {
        $seller = $this->userWithRole('vendedor');

        $this->actingAs($seller)->get(route('inventario.index'))->assertForbidden();
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
