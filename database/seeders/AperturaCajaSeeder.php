<?php

namespace Database\Seeders;

use App\Models\AperturaCaja;
use Illuminate\Database\Seeder;

class AperturaCajaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AperturaCaja::factory()->count(5)->create();
    }
}
