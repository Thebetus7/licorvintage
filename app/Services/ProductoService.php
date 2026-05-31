<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProductoService
{
    public function __construct(
        private InventarioService $inventarioService,
    ) {}

    public function create(array $data, User $user): Producto
    {
        return DB::transaction(function () use ($data, $user): Producto {
            $stock = $data['stock'];
            unset($data['stock']);

            $stockInicial = (int) $stock['stock'];
            $stock['stock'] = 0;

            $data['costo_promedio'] = $data['costo'];
            $data['codigo_qr'] = $data['codigo_qr'] ?? $data['codigo_barra'];
            $data['publicado'] = $data['publicado'] ?? true;

            $producto = Producto::create($data);
            $producto->stocks()->create($stock);

            if ($stockInicial > 0) {
                $this->inventarioService->registrarIngreso(
                    $producto,
                    $stockInicial,
                    (float) $data['costo'],
                    'ingreso_inicial',
                    null,
                    $user,
                    'Stock inicial al crear producto',
                );
            }

            return $producto->load('stockActual');
        });
    }

    public function update(Producto $producto, array $data): Producto
    {
        return DB::transaction(function () use ($producto, $data): Producto {
            $stockLimits = $data['stock'] ?? null;
            unset($data['stock']);

            $data['codigo_qr'] = $data['codigo_qr'] ?? $data['codigo_barra'] ?? $producto->codigo_qr;

            $producto->update($data);

            if ($stockLimits) {
                $producto->stockActual()->updateOrCreate(
                    ['producto_id' => $producto->id],
                    [
                        'min' => $stockLimits['min'],
                        'max' => $stockLimits['max'],
                    ],
                );
            }

            return $producto->load('stockActual');
        });
    }
}
