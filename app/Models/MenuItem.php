<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = [
        'label',
        'route_name',
        'roles',
    ];

    protected function casts(): array
    {
        return [
            'roles' => 'array',
        ];
    }
}
