<?php

namespace Database\Factories;

use App\Models\DetallePromo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VentaFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'monto_pagado' => fake()->randomFloat(0, 0, 9999999999.),
            'cod_descuento' => fake()->word(),
            'monto_original' => fake()->randomFloat(0, 0, 9999999999.),
            'monto_final' => fake()->randomFloat(0, 0, 9999999999.),
            'nro_cuotas' => fake()->numberBetween(-10000, 10000),
            'tipo_pago' => fake()->word(),
            'detalle_promo_id' => DetallePromo::factory(),
            'user_id' => User::factory(),
        ];
    }
}
