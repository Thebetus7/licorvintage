<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bitacora extends Model
{
    protected $table = 'bitacora';

    public $timestamps = false; // Only created_at is managed in migration (useCurrent)

    protected $fillable = [
        'tipo',
        'user_id',
        'descripcion',
        'ip_address',
        'user_agent',
        'estado',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
