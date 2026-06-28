<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'codigo_lote',
        'producto_id',
        'compra_id',
        'proveedor_id',
        'cantidad_inicial',
        'cantidad_actual',
        'fecha_ingreso',
        'fecha_expiracion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'producto_id' => 'integer',
            'compra_id' => 'integer',
            'proveedor_id' => 'integer',
            'cantidad_inicial' => 'integer',
            'cantidad_actual' => 'integer',
            'fecha_ingreso' => 'date',
            'fecha_expiracion' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Lote $lote) {
            $lote->update([
                'codigo_lote' => 'LOT-' . str_pad($lote->id, 6, '0', STR_PAD_LEFT)
            ]);
        });
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }
}
