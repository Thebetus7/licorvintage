<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $productos = [
            ['nombre' => 'Singani Los Parrales', 'mililitros' => 750, 'costo' => 45.00, 'precio_venta' => 75.00, 'codigo_barra' => 'LV-100001', 'stock' => 24, 'min' => 6, 'max' => 40],
            ['nombre' => 'Ron Bacardi Carta Blanca', 'mililitros' => 750, 'costo' => 55.00, 'precio_venta' => 90.00, 'codigo_barra' => 'LV-100002', 'stock' => 18, 'min' => 4, 'max' => 30],
            ['nombre' => 'Whisky Johnnie Walker Red', 'mililitros' => 750, 'costo' => 120.00, 'precio_venta' => 185.00, 'codigo_barra' => 'LV-100003', 'stock' => 12, 'min' => 3, 'max' => 25],
            ['nombre' => 'Vodka Absolut', 'mililitros' => 750, 'costo' => 85.00, 'precio_venta' => 130.00, 'codigo_barra' => 'LV-100004', 'stock' => 15, 'min' => 4, 'max' => 28],
            ['nombre' => 'Gin Beefeater London Dry', 'mililitros' => 750, 'costo' => 95.00, 'precio_venta' => 145.00, 'codigo_barra' => 'LV-100005', 'stock' => 10, 'min' => 2, 'max' => 20],
            ['nombre' => 'Tequila Olmeca Silver', 'mililitros' => 750, 'costo' => 78.00, 'precio_venta' => 120.00, 'codigo_barra' => 'LV-100006', 'stock' => 14, 'min' => 3, 'max' => 24],
            ['nombre' => 'Pisco Capel Reservado', 'mililitros' => 750, 'costo' => 62.00, 'precio_venta' => 98.00, 'codigo_barra' => 'LV-100007', 'stock' => 16, 'min' => 4, 'max' => 26],
            ['nombre' => 'Vino Casillero del Diablo Cabernet', 'mililitros' => 750, 'costo' => 48.00, 'precio_venta' => 78.00, 'codigo_barra' => 'LV-100008', 'stock' => 30, 'min' => 8, 'max' => 50],
            ['nombre' => 'Vino Concha y Toro Merlot', 'mililitros' => 750, 'costo' => 42.00, 'precio_venta' => 70.00, 'codigo_barra' => 'LV-100009', 'stock' => 28, 'min' => 8, 'max' => 45],
            ['nombre' => 'Cerveza Paceña 620ml', 'mililitros' => 620, 'costo' => 8.50, 'precio_venta' => 15.00, 'codigo_barra' => 'LV-100010', 'stock' => 48, 'min' => 12, 'max' => 80],
            ['nombre' => 'Cerveza Huari 620ml', 'mililitros' => 620, 'costo' => 8.00, 'precio_venta' => 14.00, 'codigo_barra' => 'LV-100011', 'stock' => 52, 'min' => 12, 'max' => 90],
            ['nombre' => 'Licor Baileys Original', 'mililitros' => 750, 'costo' => 110.00, 'precio_venta' => 165.00, 'codigo_barra' => 'LV-100012', 'stock' => 9, 'min' => 2, 'max' => 18],
            ['nombre' => 'Amaretto Disaronno', 'mililitros' => 700, 'costo' => 135.00, 'precio_venta' => 195.00, 'codigo_barra' => 'LV-100013', 'stock' => 8, 'min' => 2, 'max' => 16],
            ['nombre' => 'Champagne Chandon Brut', 'mililitros' => 750, 'costo' => 150.00, 'precio_venta' => 220.00, 'codigo_barra' => 'LV-100014', 'stock' => 6, 'min' => 2, 'max' => 12],
            ['nombre' => 'Brandy Torres 10', 'mililitros' => 700, 'costo' => 88.00, 'precio_venta' => 135.00, 'codigo_barra' => 'LV-100015', 'stock' => 11, 'min' => 3, 'max' => 22],
            ['nombre' => 'Ron Zacapa 23', 'mililitros' => 750, 'costo' => 210.00, 'precio_venta' => 310.00, 'codigo_barra' => 'LV-100016', 'stock' => 5, 'min' => 1, 'max' => 10],
            ['nombre' => 'Whisky Jack Daniels', 'mililitros' => 750, 'costo' => 115.00, 'precio_venta' => 175.00, 'codigo_barra' => 'LV-100017', 'stock' => 13, 'min' => 3, 'max' => 24],
            ['nombre' => 'Vermut Cinzano Rosso', 'mililitros' => 950, 'costo' => 38.00, 'precio_venta' => 62.00, 'codigo_barra' => 'LV-100018', 'stock' => 20, 'min' => 5, 'max' => 35],
            ['nombre' => 'Aperol', 'mililitros' => 750, 'costo' => 72.00, 'precio_venta' => 110.00, 'codigo_barra' => 'LV-100019', 'stock' => 10, 'min' => 2, 'max' => 20],
            ['nombre' => 'Singani 1889 Premium', 'mililitros' => 750, 'costo' => 58.00, 'precio_venta' => 92.00, 'codigo_barra' => 'LV-100020', 'stock' => 17, 'min' => 4, 'max' => 28, 'publicado' => false],
        ];

        foreach ($productos as $data) {
            $stock = [
                'stock' => $data['stock'],
                'min' => $data['min'],
                'max' => $data['max'],
            ];

            unset($data['stock'], $data['min'], $data['max']);

            $producto = Producto::updateOrCreate(
                ['codigo_barra' => $data['codigo_barra']],
                [
                    ...$data,
                    'costo_promedio' => $data['costo'],
                    'codigo_qr' => $data['codigo_barra'],
                    'descripcion' => 'Producto de licoreria Licor Vintage.',
                    'imagen' => null,
                    'fotos' => null,
                    'publicado' => $data['publicado'] ?? true,
                ],
            );

            $producto->stocks()->updateOrCreate(
                ['producto_id' => $producto->id],
                $stock,
            );
        }
    }
}
