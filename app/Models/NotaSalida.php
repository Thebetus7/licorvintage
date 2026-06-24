<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaSalida extends Model
{
    use SoftDeletes;

    protected $table = 'notaSalida';

    protected $fillable = [
        'tiposSalida_id',
        'fecha',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'estado' => 'boolean',
        ];
    }

    public function tipoSalida(): BelongsTo
    {
        return $this->belongsTo(TipoSalida::class, 'tiposSalida_id');
    }

    public function salidaDetalles(): HasMany
    {
        return $this->hasMany(SalidaDetalle::class, 'notaSalida_id');
    }
}
