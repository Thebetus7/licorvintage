<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetalleCuota extends Model
{
    protected $table = 'detalleCuotas';

    protected $fillable = [
        'venta_id',
        'cuotas_id',
        'monto',
        'fecha',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto' => 'decimal:2',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function cuota(): BelongsTo
    {
        return $this->belongsTo(Cuota::class, 'cuotas_id');
    }

    public function transacciones(): HasMany
    {
        return $this->hasMany(Transaccion::class, 'detalleCuotas_id');
    }
}
