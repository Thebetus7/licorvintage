<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoSalida extends Model
{
    use SoftDeletes;

    protected $table = 'tiposSalida';

    protected $fillable = [
        'descripcion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    public function notasSalida(): HasMany
    {
        return $this->hasMany(NotaSalida::class, 'tiposSalida_id');
    }
}
