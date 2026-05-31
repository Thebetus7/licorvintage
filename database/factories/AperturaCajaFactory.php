<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AperturaCajaFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'monto_inicial' => fake()->randomFloat(0, 0, 9999999999.),
            'monto_sistema' => fake()->randomFloat(0, 0, 9999999999.),
            'monto_real' => fake()->randomFloat(0, 0, 9999999999.),
            'diferencia' => fake()->randomFloat(0, 0, 9999999999.),
            'tiempo_apertura' => fake()->dateTime(),
            'tiempo_cierre' => fake()->dateTime(),
            'estado' => fake()->word(),
            'user_id' => User::factory(),
        ];
    }
}
