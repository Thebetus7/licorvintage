<?php

namespace Database\Factories;

use App\Models\Producto;
use App\Models\Promocion;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetallePromoFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'producto_id' => Producto::factory(),
            'promocion_id' => Promocion::factory(),
        ];
    }
}
