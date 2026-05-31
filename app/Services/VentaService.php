<?php

namespace App\Services;

use App\Models\AperturaCaja;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VentaService
{
    public function __construct(
        private InventarioService $inventarioService,
    ) {}

    public function create(array $data, User $user, AperturaCaja $caja): Venta
    {
        return DB::transaction(function () use ($data, $user, $caja): Venta {
            $detalles = collect($data['detalles'])->map(function (array $detalle): array {
                $producto = Producto::query()->with('stockActual')->findOrFail($detalle['producto_id']);
                $stock = $producto->stockActual;

                if (! $stock || $stock->stock < $detalle['cantidad']) {
                    throw ValidationException::withMessages([
                        'detalles' => "Stock insuficiente para {$producto->nombre}.",
                    ]);
                }

                return [
                    'producto' => $producto,
                    'cantidad' => (int) $detalle['cantidad'],
                    'precio' => (float) $producto->precio_venta,
                    'subtotal' => (float) $producto->precio_venta * (int) $detalle['cantidad'],
                ];
            });

            $total = $detalles->sum('subtotal');

            if ($data['monto_pagado'] < $total) {
                throw ValidationException::withMessages([
                    'monto_pagado' => 'El monto pagado no cubre el total de la venta.',
                ]);
            }

            $venta = Venta::create([
                'monto_pagado' => $data['monto_pagado'],
                'cod_descuento' => null,
                'monto_original' => $total,
                'monto_final' => $total,
                'nro_cuotas' => 1,
                'tipo_pago' => $data['tipo_pago'],
                'detalle_promo_id' => null,
                'user_id' => $user->id,
            ]);

            foreach ($detalles as $detalle) {
                $venta->detalleVentas()->create([
                    'cantidad' => $detalle['cantidad'],
                    'precio_original' => $detalle['precio'],
                    'descuento' => 0,
                    'precio_u_final' => $detalle['precio'],
                    'subtotal' => $detalle['subtotal'],
                    'producto_id' => $detalle['producto']->id,
                ]);

                $this->inventarioService->registrarSalida(
                    $detalle['producto'],
                    $detalle['cantidad'],
                    'salida_venta',
                    $venta,
                    $user,
                    "Venta #{$venta->id}",
                );
            }

            $venta->metodoPagos()->create(['tipo_pago' => $data['tipo_pago']]);
            $caja->movimientoCajas()->create([
                'monto' => $total,
                'tipo' => 'venta',
                'detalle' => "Venta #{$venta->id}",
            ]);
            $caja->increment('monto_sistema', $total);

            return $venta->load('detalleVentas.producto');
        });
    }
}
