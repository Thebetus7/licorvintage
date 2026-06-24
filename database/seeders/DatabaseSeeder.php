<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Rol;
use App\Models\MetodoPago;
use App\Models\TipoSalida;
use App\Models\Cuota;
use App\Models\Producto;
use App\Models\Stock;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Roles
        $roles = ['Propietario', 'Vendedor', 'Cliente'];
        foreach ($roles as $roleName) {
            Rol::updateOrCreate(
                ['nombre' => $roleName],
                ['nombre' => $roleName]
            );
        }

        // 2. Seed Payment Methods
        $paymentMethods = ['Efectivo', 'Tarjeta', 'QR'];
        foreach ($paymentMethods as $methodName) {
            MetodoPago::updateOrCreate(
                ['nombre' => $methodName],
                ['nombre' => $methodName]
            );
        }

        // 3. Seed Types of Output
        $outputTypes = ['Rotura', 'Consumo interno', 'Vencimiento'];
        foreach ($outputTypes as $typeName) {
            TipoSalida::updateOrCreate(
                ['descripcion' => $typeName],
                ['descripcion' => $typeName, 'estado' => true]
            );
        }

        // 4. Seed Installment Plans
        $plans = [
            ['descripcion' => 'Plan 2 Cuotas', 'cantidadesCuotas' => 2],
            ['descripcion' => 'Plan 3 Cuotas', 'cantidadesCuotas' => 3],
        ];
        foreach ($plans as $plan) {
            Cuota::updateOrCreate(
                ['descripcion' => $plan['descripcion']],
                ['cantidadesCuotas' => $plan['cantidadesCuotas']]
            );
        }

        // 5. Seed default Owner user (Propietario)
        $ownerRole = Rol::where('nombre', 'Propietario')->first();

        User::updateOrCreate([
            'email' => 'admin@gmail.com',
        ], [
            'nombre' => 'Propietario',
            'password' => Hash::make('123456789'),
            'rol_id' => $ownerRole->id,
            'estado' => true,
        ]);

        // 6. Seed some default Products and Stock for display
        $productos = [
            [
                'nombre' => 'Fernet Branca 750ml',
                'descripcion' => 'Licor de hierbas amargo elaborado a partir de varios tipos de hierbas, maceradas en alcohol de uva.',
                'precio' => 120.00,
                'stock' => 50,
            ],
            [
                'nombre' => 'Whisky Johnnie Walker Red Label 1L',
                'descripcion' => 'Whisky escocés de mezcla, caracterizado por su sabor intenso, especiado y ahumado.',
                'precio' => 180.00,
                'stock' => 30,
            ],
            [
                'nombre' => 'Ron Flor de Caña 7 Años 750ml',
                'descripcion' => 'Ron premium de Nicaragua de cuerpo entero y color caoba, envejecido lentamente de forma natural.',
                'precio' => 150.00,
                'stock' => 25,
            ],
            [
                'nombre' => 'Vodka Absolut 750ml',
                'descripcion' => 'Vodka sueco premium elaborado exclusivamente a partir de ingredientes naturales de la más alta calidad.',
                'precio' => 110.00,
                'stock' => 40,
            ],
            [
                'nombre' => 'Tequila José Cuervo Especial Reposado 750ml',
                'descripcion' => 'Tequila mexicano reposado en barricas de roble doblemente destilado, de sabor suave y equilibrado.',
                'precio' => 160.00,
                'stock' => 20,
            ],
            [
                'nombre' => 'Cerveza Paceña Centenario 620ml',
                'descripcion' => 'Cerveza tipo pilsener boliviana, elaborada con agua de la cordillera de los Andes e ingredientes seleccionados.',
                'precio' => 15.00,
                'stock' => 200,
            ]
        ];

        foreach ($productos as $pData) {
            $prod = Producto::updateOrCreate(
                ['nombre' => $pData['nombre']],
                [
                    'descripcion' => $pData['descripcion'],
                    'precio' => $pData['precio'],
                    'estado' => true,
                ]
            );

            Stock::updateOrCreate(
                ['producto_id' => $prod->id],
                [
                    'cantidad' => $pData['stock'],
                    'min' => 5,
                    'max' => 500,
                ]
            );
        }
    }
}
