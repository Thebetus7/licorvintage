<?php

namespace Database\Factories;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'stock' => fake()->numberBetween(-10000, 10000),
            'min' => fake()->numberBetween(-10000, 10000),
            'max' => fake()->numberBetween(-10000, 10000),
            'producto_id' => Producto::factory(),
        ];
    }
}
