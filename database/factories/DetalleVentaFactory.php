<?php

namespace Database\Factories;

use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetalleVentaFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'cantidad' => fake()->numberBetween(-10000, 10000),
            'precio_original' => fake()->randomFloat(0, 0, 9999999999.),
            'descuento' => fake()->randomFloat(0, 0, 9999999999.),
            'precio_u_final' => fake()->randomFloat(0, 0, 9999999999.),
            'subtotal' => fake()->randomFloat(0, 0, 9999999999.),
            'venta_id' => Venta::factory(),
            'producto_id' => Producto::factory(),
        ];
    }
}
