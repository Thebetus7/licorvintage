<?php

namespace Database\Seeders;

use App\Models\MovimientoCaja;
use Illuminate\Database\Seeder;

class MovimientoCajaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MovimientoCaja::factory()->count(5)->create();
    }
}
