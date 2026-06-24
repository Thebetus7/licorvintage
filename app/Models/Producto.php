<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'mililitros',
        'costo',
        'costo_promedio',
        'precio_venta',
        'descripcion',
        'imagen',
        'fotos',
        'codigo_barra',
        'codigo_qr',
        'publicado',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'mililitros' => 'integer',
            'costo' => 'decimal:2',
            'costo_promedio' => 'decimal:2',
            'precio_venta' => 'decimal:2',
            'fotos' => 'array',
            'publicado' => 'boolean',
        ];
    }

    public function detalleVentas(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function detallePromos(): HasMany
    {
        return $this->hasMany(DetallePromo::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function stockActual(): HasOne
    {
        return $this->hasOne(Stock::class);
    }
}
