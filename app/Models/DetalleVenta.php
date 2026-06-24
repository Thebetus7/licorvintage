<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleVenta extends Model
{
    protected $table = 'detalleVenta';

    protected $fillable = [
        'venta_id',
        'producto_id',
        'cantidad',
        'precioVenta',
        'subTotal',
        'descuento',
    ];

    protected function casts(): array
    {
        return [
            'precioVenta' => 'decimal:2',
            'subTotal' => 'decimal:2',
            'descuento' => 'decimal:2',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
