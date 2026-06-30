<?php

namespace Database\Seeders;

use App\Models\DetallePromo;
use App\Models\Producto;
use App\Models\Promocion;
use Illuminate\Database\Seeder;

class DetallePromoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $promociones = Promocion::all();
        $productos = Producto::all();

        foreach ($promociones as $promo) {
            // Asignar entre 10 y 20 productos aleatorios a cada promoción
            $randomProductos = $productos->random(rand(10, 20));
            foreach ($randomProductos as $prod) {
                DetallePromo::create([
                    'producto_id' => $prod->id,
                    'promocion_id' => $promo->id,
                ]);
            }
        }
    }
}
