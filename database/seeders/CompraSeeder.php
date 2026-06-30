<?php

namespace Database\Seeders;

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CompraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $proveedores = Proveedor::all();
        $productos = Producto::all();
        $admin = User::role('propietario')->first();

        if ($proveedores->isEmpty() || $productos->isEmpty() || !$admin) {
            return;
        }

        // Hacemos 25 compras distribuidas desde hace 18 meses hasta hoy
        for ($c = 1; $c <= 25; $c++) {
            $fechaCompra = Carbon::now()->subMonths(20 - $c)->subDays(rand(1, 28))->setHour(rand(9, 17))->setMinute(rand(0, 59));
            
            $compra = Compra::create([
                'costo' => 0.0, // Se actualizará al final
                'proveedor_id' => $proveedores->random()->id,
                'user_id' => $admin->id,
                'created_at' => $fechaCompra,
                'updated_at' => $fechaCompra,
            ]);

            $totalCosto = 0.0;
            // Cada compra adquiere entre 15 y 25 productos aleatorios
            $compraProductos = $productos->random(rand(15, 25));
            
            foreach ($compraProductos as $prod) {
                $cantidad = rand(150, 300);
                $subCosto = $prod->costo * $cantidad;
                $totalCosto += $subCosto;

                DetalleCompra::create([
                    'cantidad' => $cantidad,
                    'sub_costo' => $subCosto,
                    'compra_id' => $compra->id,
                    'producto_id' => $prod->id,
                    'created_at' => $fechaCompra,
                    'updated_at' => $fechaCompra,
                ]);

                // Crear el Lote correspondiente
                $lote = Lote::create([
                    'producto_id' => $prod->id,
                    'proveedor_id' => $compra->proveedor_id,
                    'cantidad_inicial' => $cantidad,
                    'cantidad_actual' => $cantidad,
                    'fecha_ingreso' => $fechaCompra,
                    'fecha_expiracion' => $fechaCompra->copy()->addYears(rand(2, 4)),
                    'estado' => 'activo',
                    'created_at' => $fechaCompra,
                    'updated_at' => $fechaCompra,
                ]);

                // Actualizar o crear Stock
                $stock = Stock::where('producto_id', $prod->id)->first();
                if ($stock) {
                    $stock->stock += $cantidad;
                    $stock->save();
                } else {
                    $stock = Stock::create([
                        'stock' => $cantidad,
                        'min' => 10,
                        'max' => 2000,
                        'producto_id' => $prod->id,
                        'created_at' => $fechaCompra,
                        'updated_at' => $fechaCompra,
                    ]);
                }

                // Registrar Movimiento de Inventario (Entrada)
                MovimientoInventario::create([
                    'tipo' => 'ingreso_compra',
                    'cantidad' => $cantidad,
                    'costo_unitario' => $prod->costo,
                    'saldo_cantidad' => $stock->stock,
                    'saldo_costo_promedio' => $prod->costo,
                    'motivo' => 'Compra a proveedor',
                    'producto_id' => $prod->id,
                    'lote_id' => $lote->id,
                    'user_id' => $admin->id,
                    'created_at' => $fechaCompra,
                    'updated_at' => $fechaCompra,
                ]);
            }

            // Actualizar costo total de la compra
            $compra->update(['costo' => $totalCosto]);
        }
    }
}
