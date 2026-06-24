<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cuota extends Model
{
    use SoftDeletes;

    protected $table = 'cuotas';

    protected $fillable = [
        'descripcion',
        'cantidadesCuotas',
    ];

    public function detalleCuotas(): HasMany
    {
        return $this->hasMany(DetalleCuota::class, 'cuotas_id');
    }
}
