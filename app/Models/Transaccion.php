<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaccion extends Model
{
    protected $table = 'transaccion';

    protected $fillable = [
        'venta_id',
        'aperturaCaja_id',
        'metodoPago_id',
        'detalleCuotas_id',
        'monto',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function aperturaCaja(): BelongsTo
    {
        return $this->belongsTo(AperturaCaja::class, 'aperturaCaja_id');
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'metodoPago_id');
    }

    public function detalleCuota(): BelongsTo
    {
        return $this->belongsTo(DetalleCuota::class, 'detalleCuotas_id');
    }
}
