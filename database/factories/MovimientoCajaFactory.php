<?php

namespace Database\Factories;

use App\Models\AperturaCaja;
use Illuminate\Database\Eloquent\Factories\Factory;

class MovimientoCajaFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'monto' => fake()->randomFloat(0, 0, 9999999999.),
            'tipo' => fake()->word(),
            'detalle' => fake()->text(),
            'apertura_caja_id' => AperturaCaja::factory(),
        ];
    }
}
