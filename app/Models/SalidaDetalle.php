<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalidaDetalle extends Model
{
    protected $table = 'salidaDetalle';

    protected $fillable = [
        'notaSalida_id',
        'producto_id',
        'cantidad',
    ];

    public function notaSalida(): BelongsTo
    {
        return $this->belongsTo(NotaSalida::class, 'notaSalida_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
