<?php

namespace Database\Seeders;

use App\Models\Promocion;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PromocionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $promocionesData = [
            [
                'nombre_promo' => 'Black Friday',
                'codigo_promo' => 'BLACKFRIDAY',
                'descuento' => 15.00,
                'tipo_descuento' => 'porcentaje',
                'fecha_inicio' => Carbon::create(2025, 11, 20),
                'fecha_fin' => Carbon::create(2026, 12, 31)
            ],
            [
                'nombre_promo' => 'Año Nuevo 2026',
                'codigo_promo' => 'NEWYEAR2026',
                'descuento' => 10.00,
                'tipo_descuento' => 'porcentaje',
                'fecha_inicio' => Carbon::create(2025, 12, 25),
                'fecha_fin' => Carbon::create(2026, 1, 5)
            ],
            [
                'nombre_promo' => 'Descuento Carnavalero',
                'codigo_promo' => 'CARNAVAL2026',
                'descuento' => 20.00,
                'tipo_descuento' => 'porcentaje',
                'fecha_inicio' => Carbon::create(2026, 2, 1),
                'fecha_fin' => Carbon::create(2026, 2, 28)
            ],
            [
                'nombre_promo' => 'Día de la Madre',
                'codigo_promo' => 'MAMA2026',
                'descuento' => 12.00,
                'tipo_descuento' => 'porcentaje',
                'fecha_inicio' => Carbon::create(2026, 5, 20),
                'fecha_fin' => Carbon::create(2026, 5, 31)
            ],
            [
                'nombre_promo' => 'Bienvenida Vintage',
                'codigo_promo' => 'VINTAGE5',
                'descuento' => 5.00,
                'tipo_descuento' => 'porcentaje',
                'fecha_inicio' => Carbon::create(2025, 1, 1),
                'fecha_fin' => Carbon::create(2026, 12, 31)
            ],
        ];

        foreach ($promocionesData as $promo) {
            Promocion::create($promo);
        }
    }
}
