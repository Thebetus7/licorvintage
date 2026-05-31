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
            'monto_inicial' => 'double',
            'monto_sistema' => 'double',
            'monto_real' => 'double',
            'diferencia' => 'double',
            'tiempo_apertura' => 'datetime',
            'tiempo_cierre' => 'datetime',
            'user_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movimientoCajas(): HasMany
    {
        return $this->hasMany(MovimientoCaja::class);
    }
}
