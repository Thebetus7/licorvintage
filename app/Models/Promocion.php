<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promocion extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nombre_promo',
        'codigo_promo',
        'descuento',
        'tipo_descuento',
        'fecha_inicio',
        'fecha_fin',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'descuento' => 'double',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function detallePromos(): HasMany
    {
        return $this->hasMany(DetallePromo::class);
    }
}
