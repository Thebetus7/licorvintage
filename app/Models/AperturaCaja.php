<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AperturaCaja extends Model
{
    protected $table = 'aperturaCaja';

    protected $fillable = [
        'user_id',
        'fechaInicio',
        'fechaCierre',
        'totalEfectivo',
        'totalQR',
        'totalTarjeta',
        'totalSistema',
        'cajaChica',
        'realEfectivo',
        'realQR',
        'realTarjeta',
        'realTotal',
        'diferencia',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fechaInicio' => 'datetime',
            'fechaCierre' => 'datetime',
            'totalEfectivo' => 'decimal:2',
            'totalQR' => 'decimal:2',
            'totalTarjeta' => 'decimal:2',
            'totalSistema' => 'decimal:2',
            'cajaChica' => 'decimal:2',
            'realEfectivo' => 'decimal:2',
            'realQR' => 'decimal:2',
            'realTarjeta' => 'decimal:2',
            'realTotal' => 'decimal:2',
            'diferencia' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transacciones(): HasMany
    {
        return $this->hasMany(Transaccion::class, 'aperturaCaja_id');
    }
}
