<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use Illuminate\Database\Seeder;

class ProveedorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $proveedoresData = [
            ['nombre' => 'Importadora D&M S.A.', 'telefono' => '3345678', 'descripcion' => 'Distribuidor oficial de licores premium en Bolivia.'],
            ['nombre' => 'Distribuidora Licores del Sur', 'telefono' => '4456789', 'descripcion' => 'Especialistas en vinos y singanis nacionales.'],
            ['nombre' => 'Bodegas Casa Real', 'telefono' => '6621345', 'descripcion' => 'Productor y distribuidor de Singani Casa Real.'],
            ['nombre' => 'Bodegas Aranjuez S.A.', 'telefono' => '6643210', 'descripcion' => 'Productores de Vinos Aranjuez, Tarija.'],
            ['nombre' => 'Cervecería Boliviana Nacional (CBN)', 'telefono' => '3123456', 'descripcion' => 'Distribuidor de Paceña, Huari, Corona y Stella Artois.'],
            ['nombre' => 'Distribuidora El Pairumani', 'telefono' => '4412345', 'descripcion' => 'Importador de marcas internacionales de whisky y ron.'],
            ['nombre' => 'Licores Importados Global S.R.L.', 'telefono' => '3398765', 'descripcion' => 'Importaciones directas de tequila, vodka y gin.'],
            ['nombre' => 'Bebidas del Oriente S.R.L.', 'telefono' => '3387654', 'descripcion' => 'Distribución masiva de cervezas y licores populares.'],
            ['nombre' => 'Bodega Campos de Solana', 'telefono' => '6645678', 'descripcion' => 'Vinos finos de altura, Tarija.'],
            ['nombre' => 'Bebidas Premium Bolivia', 'telefono' => '71020304', 'descripcion' => 'Importadora boutique de whiskies single malt y licores raros.'],
        ];

        foreach ($proveedoresData as $prov) {
            Proveedor::create($prov);
        }
    }
}
