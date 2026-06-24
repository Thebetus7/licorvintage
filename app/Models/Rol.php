<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rol extends Model
{
    use SoftDeletes;

    protected $table = 'rol';

    protected $fillable = [
        'nombre',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }
}
