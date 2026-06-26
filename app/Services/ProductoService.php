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

            \App\Models\ActivityLog::create([
                'event_type' => 'product_created',
                'user_id' => $user->id,
                'user_identity' => $user->email,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'resource_name' => 'Productos',
                'visited_url' => request()->getRequestUri(),
                'description' => "Producto creado: {$producto->nombre} ({$producto->mililitros} ml) - Código: {$producto->codigo_barra}. Precio Venta: {$producto->precio_venta} Bs, Costo: {$producto->costo} Bs, Stock Inicial: {$stockInicial}.",
            ]);

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

            \App\Models\ActivityLog::create([
                'event_type' => 'product_updated',
                'user_id' => auth()->id(),
                'user_identity' => auth()->user()?->email ?? 'sistema',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'resource_name' => 'Productos',
                'visited_url' => request()->getRequestUri(),
                'description' => "Producto actualizado: {$producto->nombre} (ID: {$producto->id}) - Código: {$producto->codigo_barra}. Precio Venta: {$producto->precio_venta} Bs, Costo: {$producto->costo} Bs.",
            ]);

            return $producto->load('stockActual');
        });
    }
}
