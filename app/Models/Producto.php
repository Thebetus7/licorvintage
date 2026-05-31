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

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'costo' => 'double',
            'costo_promedio' => 'double',
            'precio_venta' => 'double',
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
        return $this->hasOne(Stock::class)->latestOfMany();
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    public function portada(): ?string
    {
        if (is_array($this->fotos) && count($this->fotos) > 0) {
            return $this->fotos[0];
        }

        return $this->imagen;
    }
}
