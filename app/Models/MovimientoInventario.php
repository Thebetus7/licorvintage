<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovimientoInventario extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'producto_id',
        'tipo',
        'cantidad',
        'costo_unitario',
        'saldo_cantidad',
        'saldo_costo_promedio',
        'referencia_type',
        'referencia_id',
        'motivo',
        'user_id',
        'lote_id',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'producto_id' => 'integer',
            'cantidad' => 'integer',
            'costo_unitario' => 'double',
            'saldo_cantidad' => 'integer',
            'saldo_costo_promedio' => 'double',
            'user_id' => 'integer',
            'lote_id' => 'integer',
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

    public function referencia(): MorphTo
    {
        return $this->morphTo();
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }

    public function esIngreso(): bool
    {
        return str_starts_with($this->tipo, 'ingreso_');
    }

    public function esSalida(): bool
    {
        return str_starts_with($this->tipo, 'salida_');
    }
}
