<?php

namespace Database\Seeders;

use App\Models\TipoSalida;
use Illuminate\Database\Seeder;

class TipoSalidaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipos = [
            ['nombre' => 'Consumo de Personal'],
            ['nombre' => 'Botella Rota / Merma'],
            ['nombre' => 'Vencimiento'],
            ['nombre' => 'Ajuste de Inventario'],
        ];

        foreach ($tipos as $tipo) {
            TipoSalida::firstOrCreate(['nombre' => $tipo['nombre']], $tipo);
        }
    }
}
