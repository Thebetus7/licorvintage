<?php

namespace App\Services;

use App\Models\AperturaCaja;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Models\Promocion;
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

            $totalOriginal = $detalles->sum('subtotal');
            $totalFinal = $totalOriginal;
            $promocionId = null;
            $codigoPromoApplied = null;

            // Procesar promoción si se especifica
            if (!empty($data['codigo_promo'])) {
                $promocion = Promocion::where('codigo_promo', $data['codigo_promo'])->first();

                if (!$promocion) {
                    throw ValidationException::withMessages([
                        'codigo_promo' => 'El código de promoción no existe.',
                    ]);
                }

                $hoy = today();
                if ($hoy->lt($promocion->fecha_inicio) || $hoy->gt($promocion->fecha_fin)) {
                    throw ValidationException::withMessages([
                        'codigo_promo' => 'La promoción no está vigente.',
                    ]);
                }

                $promocionId = $promocion->id;
                $codigoPromoApplied = $promocion->codigo_promo;

                if ($promocion->tipo_descuento === 'porcentaje') {
                    $descuento = $totalOriginal * ($promocion->descuento / 100);
                } else {
                    $descuento = $promocion->descuento;
                }

                $totalFinal = max(0, $totalOriginal - $descuento);
            }

            $tipoPago = $data['tipo_pago'];
            $isCredito = $tipoPago === 'credito';
            $nroCuotas = $isCredito ? (int)$data['nro_cuotas'] : 1;

            if ($isCredito) {
                if (empty($data['cliente_id'])) {
                    throw ValidationException::withMessages([
                        'cliente_id' => 'El cliente es obligatorio para ventas a crédito.',
                    ]);
                }
                if ($nroCuotas < 2) {
                    throw ValidationException::withMessages([
                        'nro_cuotas' => 'Una venta a crédito debe tener al menos 2 cuotas.',
                    ]);
                }
            } else {
                $montoPagado = (float)($data['monto_pagado'] ?? $totalFinal);
                if ($montoPagado < $totalFinal) {
                    throw ValidationException::withMessages([
                        'monto_pagado' => 'El monto pagado no cubre el total de la venta.',
                    ]);
                }
            }

            $venta = Venta::create([
                'monto_pagado' => $isCredito ? 0 : ($data['monto_pagado'] ?? $totalFinal),
                'cod_descuento' => $codigoPromoApplied,
                'monto_original' => $totalOriginal,
                'monto_final' => $totalFinal,
                'nro_cuotas' => $nroCuotas,
                'tipo_pago' => $tipoPago,
                'promocion_id' => $promocionId,
                'cliente_id' => $data['cliente_id'] ?? null,
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

            $venta->metodoPagos()->create(['tipo_pago' => $tipoPago]);

            if ($isCredito) {
                // Registrar cuotas
                $subMontoCuota = round($totalFinal / $nroCuotas, 2);
                for ($i = 1; $i <= $nroCuotas; $i++) {
                    // Ajustar céntimos en la última cuota si hay diferencias por redondeo
                    $montoCuota = ($i === $nroCuotas) ? ($totalFinal - ($subMontoCuota * ($nroCuotas - 1))) : $subMontoCuota;
                    $venta->ventaCuotas()->create([
                        'sub_monto' => $montoCuota,
                        'nro_cuota' => $i,
                        'estado' => 'pendiente',
                    ]);
                }
            } else {
                // Registrar pago al contado en caja
                $caja->movimientoCajas()->create([
                    'monto' => $totalFinal,
                    'tipo' => 'venta',
                    'detalle' => "Venta #{$venta->id} ({$tipoPago})",
                ]);
                $caja->increment('monto_sistema', $totalFinal);
            }

            return $venta->load(['detalleVentas.producto', 'cliente', 'ventaCuotas']);
        });
    }
}
