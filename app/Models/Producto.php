<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use SoftDeletes;

    protected $table = 'producto';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'estado' => 'boolean',
        ];
    }

    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class, 'producto_id');
    }

    public function stockActual(): HasOne
    {
        return $this->hasOne(Stock::class, 'producto_id');
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class, 'producto_id');
    }

    public function detallePromos(): HasMany
    {
        return $this->hasMany(DetallePromo::class, 'producto_id');
    }

    public function detalleCompras(): HasMany
    {
        return $this->hasMany(DetalleCompra::class, 'producto_id');
    }

    public function salidaDetalles(): HasMany
    {
        return $this->hasMany(SalidaDetalle::class, 'producto_id');
    }

    public function detalleVentas(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'producto_id');
    }
}
