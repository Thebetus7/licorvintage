<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->word(),
            'mililitros' => fake()->numberBetween(-10000, 10000),
            'descripcion' => fake()->text(),
            'imagen' => fake()->word(),
            'codigo_barra' => fake()->word(),
        ];
    }
}
