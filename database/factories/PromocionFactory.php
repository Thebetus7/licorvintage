<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PromocionFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nombre_promo' => fake()->word(),
            'codigo_promo' => fake()->word(),
            'descuento' => fake()->randomFloat(0, 0, 9999999999.),
            'tipo_descuento' => fake()->word(),
            'fecha_inicio' => fake()->date(),
            'fecha_fin' => fake()->date(),
        ];
    }
}
