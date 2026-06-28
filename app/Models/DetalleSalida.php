<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleSalida extends Model
{
    use HasFactory;

    protected $table = 'detalle_salidas';

    protected $fillable = [
        'nota_salida_id',
        'producto_id',
        'lote_id',
        'cantidad',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'nota_salida_id' => 'integer',
            'producto_id' => 'integer',
            'lote_id' => 'integer',
            'cantidad' => 'integer',
        ];
    }

    public function notaSalida(): BelongsTo
    {
        return $this->belongsTo(NotaSalida::class, 'nota_salida_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }
}
