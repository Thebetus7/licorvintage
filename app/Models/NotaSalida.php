<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaSalida extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'nota_salidas';

    protected $fillable = [
        'codigo_nota',
        'tipo_salida_id',
        'fecha',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'tipo_salida_id' => 'integer',
            'fecha' => 'datetime',
            'user_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (NotaSalida $nota) {
            $nota->update([
                'codigo_nota' => 'NS-' . str_pad($nota->id, 6, '0', STR_PAD_LEFT)
            ]);
        });
    }

    public function tipoSalida(): BelongsTo
    {
        return $this->belongsTo(TipoSalida::class, 'tipo_salida_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function detalleSalidas(): HasMany
    {
        return $this->hasMany(DetalleSalida::class, 'nota_salida_id');
    }
}
