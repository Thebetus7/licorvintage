<?php

namespace Database\Factories;

use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

class VentaCuotasFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'sub_monto' => fake()->randomFloat(0, 0, 9999999999.),
            'venta_id' => Venta::factory(),
        ];
    }
}
