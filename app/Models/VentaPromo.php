<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaPromo extends Model
{
    protected $table = 'ventaPromo';

    protected $fillable = [
        'venta_id',
        'promo_id',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function promo(): BelongsTo
    {
        return $this->belongsTo(Promo::class, 'promo_id');
    }
}
