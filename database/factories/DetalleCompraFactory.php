<?php

namespace Database\Factories;

use App\Models\Compra;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetalleCompraFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'cantidad' => fake()->numberBetween(-10000, 10000),
            'sub_costo' => fake()->randomFloat(0, 0, 9999999999.),
            'compra_id' => Compra::factory(),
            'producto_id' => Producto::factory(),
        ];
    }
}
