<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AperturaCaja extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'monto_inicial',
        'monto_sistema',
        'monto_real',
        'diferencia',
        'tiempo_apertura',
        'tiempo_cierre',
        'estado',
        'user_id',
        'opened_by',
        'totales_sistema',
        'totales_caja',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'monto_inicial' => 'double',
            'monto_sistema' => 'double',
            'monto_real' => 'double',
            'diferencia' => 'double',
            'tiempo_apertura' => 'datetime',
            'tiempo_cierre' => 'datetime',
            'user_id' => 'integer',
            'opened_by' => 'integer',
            'totales_sistema' => 'array',
            'totales_caja' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function movimientoCajas(): HasMany
    {
        return $this->hasMany(MovimientoCaja::class);
    }

    public function getTotalesSistemaAttribute($value): array
    {
        return array_merge([
            'efectivo' => 0,
            'qr' => 0,
            'tarjeta' => 0,
            'credito' => 0,
        ], json_decode($value, true) ?? []);
    }

    public function getTotalesCajaAttribute($value): array
    {
        return array_merge([
            'efectivo' => 0,
            'qr' => 0,
            'tarjeta' => 0,
            'credito' => 0,
        ], json_decode($value, true) ?? []);
    }
}
