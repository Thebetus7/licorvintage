<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venta extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'monto_pagado',
        'cod_descuento',
        'monto_original',
        'monto_final',
        'nro_cuotas',
        'tipo_pago',
        'detalle_promo_id',
        'cliente_id',
        'promocion_id',
        'user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'monto_pagado' => 'double',
            'monto_original' => 'double',
            'monto_final' => 'double',
            'detalle_promo_id' => 'integer',
            'cliente_id' => 'integer',
            'promocion_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function promocion(): BelongsTo
    {
        return $this->belongsTo(Promocion::class, 'promocion_id');
    }

    public function detallePromo(): BelongsTo
    {
        return $this->belongsTo(DetallePromo::class);
    }

    public function detalleVentas(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function ventaCuotas(): HasMany
    {
        return $this->hasMany(VentaCuotas::class);
    }

    public function metodoPagos(): HasMany
    {
        return $this->hasMany(MetodoPago::class);
    }
}
