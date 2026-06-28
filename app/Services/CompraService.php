<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Compra;
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
            $total = collect($detalles)->sum('sub_costo');

            $compra = Compra::create([
                'costo' => $total,
                'proveedor_id' => $data['proveedor_id'] ?? null,
                'user_id' => $user->id,
            ]);

            foreach ($detalles as $detalle) {
                $producto = Producto::query()->findOrFail($detalle['producto_id']);
                $costoUnitario = (float) $detalle['sub_costo'] / (int) $detalle['cantidad'];

                $movimiento = $this->inventarioService->registrarIngreso(
                    $producto,
                    (int) $detalle['cantidad'],
                    $costoUnitario,
                    'ingreso_compra',
                    $compra,
                    $user,
                    "Compra #{$compra->id}",
                    $detalle['fecha_expiracion'] ?? null,
                    null,
                    $compra->proveedor_id
                );

                $compra->detalleCompras()->create([
                    'producto_id' => $detalle['producto_id'],
                    'cantidad' => $detalle['cantidad'],
                    'sub_costo' => $detalle['sub_costo'],
                    'lote_id' => $movimiento->lote_id,
                ]);
            }

            ActivityLog::create([
                'event_type' => 'purchase_created',
                'user_id' => $user->id,
                'user_identity' => $user->email,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'resource_name' => 'Compras',
                'visited_url' => request()->getRequestUri(),
                'description' => "Compra registrada exitosamente (Compra #{$compra->id}) por un costo total de {$total} Bs. Proveedor: ".($compra->proveedor?->nombre ?? 'N/A').'.',
            ]);

            return $compra->load(['proveedor', 'detalleCompras.producto.stockActual', 'user']);
        });
    }

    public function update(Compra $compra, array $data, User $user): Compra
    {
        return DB::transaction(function () use ($compra, $data, $user): Compra {
            foreach ($compra->detalleCompras as $detalle) {
                $producto = Producto::query()->findOrFail($detalle->producto_id);

                $this->inventarioService->registrarSalida(
                    $producto,
                    (int) $detalle->cantidad,
                    'salida_ajuste',
                    $compra,
                    $user,
                    "Reversión compra #{$compra->id}",
                    $detalle->lote_id
                );

                if ($detalle->lote) {
                    $detalle->lote->delete();
                }
            }

            $compra->detalleCompras()->delete();

            $detalles = $data['detalles'];
            $compra->update([
                'costo' => collect($detalles)->sum('sub_costo'),
                'proveedor_id' => $data['proveedor_id'] ?? null,
            ]);

            foreach ($detalles as $detalle) {
                $producto = Producto::query()->findOrFail($detalle['producto_id']);
                $costoUnitario = (float) $detalle['sub_costo'] / (int) $detalle['cantidad'];

                $movimiento = $this->inventarioService->registrarIngreso(
                    $producto,
                    (int) $detalle['cantidad'],
                    $costoUnitario,
                    'ingreso_compra',
                    $compra,
                    $user,
                    "Compra #{$compra->id} (actualizada)",
                    $detalle['fecha_expiracion'] ?? null,
                    null,
                    $compra->proveedor_id
                );

                $compra->detalleCompras()->create([
                    'producto_id' => $detalle['producto_id'],
                    'cantidad' => $detalle['cantidad'],
                    'sub_costo' => $detalle['sub_costo'],
                    'lote_id' => $movimiento->lote_id,
                ]);
            }

            ActivityLog::create([
                'event_type' => 'purchase_updated',
                'user_id' => $user->id,
                'user_identity' => $user->email,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'resource_name' => 'Compras',
                'visited_url' => request()->getRequestUri(),
                'description' => "Compra actualizada exitosamente (Compra #{$compra->id}) por un nuevo costo total de ".collect($detalles)->sum('sub_costo').' Bs.',
            ]);

            return $compra->load(['proveedor', 'detalleCompras.producto.stockActual', 'user']);
        });
    }

    public function delete(Compra $compra, User $user): void
    {
        DB::transaction(function () use ($compra, $user): void {
            $compraId = $compra->id;
            foreach ($compra->detalleCompras as $detalle) {
                $producto = Producto::query()->findOrFail($detalle->producto_id);

                $this->inventarioService->registrarSalida(
                    $producto,
                    (int) $detalle->cantidad,
                    'salida_ajuste',
                    $compra,
                    $user,
                    "Eliminación compra #{$compra->id}",
                    $detalle->lote_id
                );

                if ($detalle->lote) {
                    $detalle->lote->delete();
                }
            }

            $compra->detalleCompras()->delete();
            $compra->delete();

            ActivityLog::create([
                'event_type' => 'purchase_deleted',
                'user_id' => $user->id,
                'user_identity' => $user->email,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'resource_name' => 'Compras',
                'visited_url' => request()->getRequestUri(),
                'description' => "Compra de licores eliminada exitosamente (Compra #{$compraId}). Se revirtió el ingreso de stock.",
            ]);
        });
    }
}
