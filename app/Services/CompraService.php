<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompraService
{
    public function __construct(
        private InventarioService $inventarioService,
    ) {}

    public function create(array $data, User $user): Compra
    {
        return DB::transaction(function () use ($data, $user): Compra {
            $detalles = $data['detalles'];
            $productoId = $detalles[0]['producto_id'];
            $total = collect($detalles)->sum('sub_costo');

            $compra = Compra::create([
                'costo' => $total,
                'producto_id' => $productoId,
                'proveedor_id' => $data['proveedor_id'] ?? null,
                'user_id' => $user->id,
            ]);

            foreach ($detalles as $detalle) {
                $compra->detalleCompras()->create($detalle);
                $producto = Producto::query()->findOrFail($detalle['producto_id']);
                $costoUnitario = (float) $detalle['sub_costo'] / (int) $detalle['cantidad'];

                $this->inventarioService->registrarIngreso(
                    $producto,
                    (int) $detalle['cantidad'],
                    $costoUnitario,
                    'ingreso_compra',
                    $compra,
                    $user,
                    "Compra #{$compra->id}",
                );
            }

            return $compra->load(['proveedor', 'detalleCompras.producto.stockActual', 'user']);
        });
    }

    public function update(Compra $compra, array $data, User $user): Compra
    {
        return DB::transaction(function () use ($compra, $data, $user): Compra {
            foreach ($compra->detalleCompras as $detalle) {
                $producto = Producto::query()->findOrFail($detalle->producto_id);
                $costoUnitario = (float) $detalle->sub_costo / (int) $detalle->cantidad;

                $this->inventarioService->registrarSalida(
                    $producto,
                    (int) $detalle->cantidad,
                    'salida_ajuste',
                    $compra,
                    $user,
                    "Reversión compra #{$compra->id}",
                );
            }

            $compra->detalleCompras()->delete();

            $detalles = $data['detalles'];
            $compra->update([
                'costo' => collect($detalles)->sum('sub_costo'),
                'producto_id' => $detalles[0]['producto_id'],
                'proveedor_id' => $data['proveedor_id'] ?? null,
            ]);

            foreach ($detalles as $detalle) {
                $compra->detalleCompras()->create($detalle);
                $producto = Producto::query()->findOrFail($detalle['producto_id']);
                $costoUnitario = (float) $detalle['sub_costo'] / (int) $detalle['cantidad'];

                $this->inventarioService->registrarIngreso(
                    $producto,
                    (int) $detalle['cantidad'],
                    $costoUnitario,
                    'ingreso_compra',
                    $compra,
                    $user,
                    "Compra #{$compra->id} (actualizada)",
                );
            }

            return $compra->load(['proveedor', 'detalleCompras.producto.stockActual', 'user']);
        });
    }
}
