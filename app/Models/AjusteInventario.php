<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AjusteInventario extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'producto_id',
        'stock_sistema',
        'stock_fisico',
        'diferencia',
        'motivo',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'producto_id' => 'integer',
            'stock_sistema' => 'integer',
            'stock_fisico' => 'integer',
            'diferencia' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movimientos(): MorphMany
    {
        return $this->morphMany(MovimientoInventario::class, 'referencia');
    }
}
