<?php

namespace Database\Factories;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompraFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'costo' => fake()->randomFloat(0, 0, 9999999999.),
            'producto_id' => Producto::factory(),
            'user_id' => User::factory(),
        ];
    }
}
