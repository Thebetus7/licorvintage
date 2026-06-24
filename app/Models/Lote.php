<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lote extends Model
{
    protected $table = 'lote';

    protected $fillable = [
        'producto_id',
        'cantidad',
        'fechaExpiracion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fechaExpiracion' => 'date',
            'estado' => 'boolean',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
