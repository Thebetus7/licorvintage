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
            'mililitros' => fake()->numberBetween(250, 1000),
            'costo' => fake()->randomFloat(2, 10, 200),
            'costo_promedio' => fake()->randomFloat(2, 10, 200),
            'precio_venta' => fake()->randomFloat(2, 20, 300),
            'descripcion' => fake()->text(),
            'imagen' => fake()->url(),
            'fotos' => [fake()->url()],
            'codigo_barra' => fake()->unique()->bothify('PRD-####'),
            'codigo_qr' => fake()->bothify('QR-####'),
            'publicado' => true,
        ];
    }
}
