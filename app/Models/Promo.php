<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promo extends Model
{
    use SoftDeletes;

    protected $table = 'promo';

    protected $fillable = [
        'nombre',
        'descuento',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'descuento' => 'decimal:2',
            'estado' => 'boolean',
        ];
    }

    public function detallePromos(): HasMany
    {
        return $this->hasMany(DetallePromo::class, 'promo_id');
    }

    public function ventaPromos(): HasMany
    {
        return $this->hasMany(VentaPromo::class, 'promo_id');
    }
}
