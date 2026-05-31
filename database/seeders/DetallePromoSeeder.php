<?php

namespace Database\Seeders;

use App\Models\DetallePromo;
use Illuminate\Database\Seeder;

class DetallePromoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DetallePromo::factory()->count(5)->create();
    }
}
