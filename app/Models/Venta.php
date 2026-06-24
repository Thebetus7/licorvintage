<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venta extends Model
{
    use SoftDeletes;

    protected $table = 'venta';

    protected $fillable = [
        'user_id',
        'fecha',
        'descuento',
        'totalOriginal',
        'total',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'descuento' => 'decimal:2',
            'totalOriginal' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalleVentas(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    public function ventaPromos(): HasMany
    {
        return $this->hasMany(VentaPromo::class, 'venta_id');
    }

    public function detalleCuotas(): HasMany
    {
        return $this->hasMany(DetalleCuota::class, 'venta_id');
    }

    public function transacciones(): HasMany
    {
        return $this->hasMany(Transaccion::class, 'venta_id');
    }
}
