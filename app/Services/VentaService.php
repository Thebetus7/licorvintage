<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AperturaCaja;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VentaService
{
    public function __construct(
        private InventarioService $inventarioService,
        private StripeService $stripeService,
    ) {}

    public function create(array $data, User $user, AperturaCaja $caja): Venta
    {
        return DB::transaction(function () use ($data, $user, $caja): Venta {
            $detalles = collect($data['detalles'])->map(function (array $detalle) use ($user): array {
                $producto = Producto::query()->with('stockActual')->findOrFail($detalle['producto_id']);
                $stock = $producto->stockActual;

                if (! $stock || $stock->stock < $detalle['cantidad']) {
                    $disponibles = $stock ? $stock->stock : 0;
                    $this->logFailure($user, "Stock insuficiente para el producto {$producto->nombre} (Solicitado: {$detalle['cantidad']}, Disponible: {$disponibles})");
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
            if (! empty($data['codigo_promo'])) {
                $promocion = Promocion::where('codigo_promo', $data['codigo_promo'])->first();

                if (! $promocion) {
                    $this->logFailure($user, "El código de promoción '{$data['codigo_promo']}' no existe");
                    throw ValidationException::withMessages([
                        'codigo_promo' => 'El código de promoción no existe.',
                    ]);
                }

                $hoy = today();
                if ($hoy->lt($promocion->fecha_inicio) || $hoy->gt($promocion->fecha_fin)) {
                    $this->logFailure($user, "La promoción '{$promocion->nombre_promo}' no está vigente (Vigencia: {$promocion->fecha_inicio} al {$promocion->fecha_fin})");
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
            $nroCuotas = $isCredito ? (int) $data['nro_cuotas'] : 1;
            $paymentMethods = $data['payment_methods'] ?? null;

            $hasMultipleMethods = is_array($paymentMethods) && count($paymentMethods) > 0;

            if ($isCredito) {
                if (empty($data['cliente_id'])) {
                    $this->logFailure($user, 'Cliente no especificado en venta a crédito');
                    throw ValidationException::withMessages([
                        'cliente_id' => 'El cliente es obligatorio para ventas a crédito.',
                    ]);
                }
                if ($nroCuotas < 2) {
                    $this->logFailure($user, "Se intentó registrar una venta a crédito con menos de 2 cuotas (Recibido: {$nroCuotas})");
                    throw ValidationException::withMessages([
                        'nro_cuotas' => 'Una venta a crédito debe tener al menos 2 cuotas.',
                    ]);
                }
            } else {
                $montoPagado = (float) ($data['monto_pagado'] ?? $totalFinal);
                if ($montoPagado < $totalFinal) {
                    $this->logFailure($user, "Monto pagado ({$montoPagado} Bs) es menor al costo total final ({$totalFinal} Bs)");
                    throw ValidationException::withMessages([
                        'monto_pagado' => 'El monto pagado no cubre el total de la venta.',
                    ]);
                }

                // Stripe charge if tarjeta is included
                $hasTarjeta = $hasMultipleMethods
                    ? collect($paymentMethods)->contains('tipo_pago', 'tarjeta')
                    : $tipoPago === 'tarjeta';

                if ($hasTarjeta) {
                    $expiry = explode('/', $data['card_expiry'] ?? '');
                    $expMonth = trim($expiry[0] ?? '');
                    $expYear = trim($expiry[1] ?? '');

                    if (strlen($expYear) === 2) {
                        $expYear = '20'.$expYear;
                    }

                    $cardDetails = [
                        'number' => $data['card_number'] ?? '',
                        'exp_month' => $expMonth,
                        'exp_year' => $expYear,
                        'cvc' => $data['card_cvc'] ?? '',
                    ];

                    $stripeResult = $this->stripeService->chargeWithCard(
                        $totalFinal,
                        $cardDetails,
                        "Venta Licor Vintage #{$user->email}"
                    );

                    if (! $stripeResult['success']) {
                        $this->logFailure($user, "Pago con tarjeta fallido mediante pasarela Stripe. Motivo: '{$stripeResult['message']}'");
                        throw ValidationException::withMessages([
                            'card_number' => $stripeResult['message'],
                        ]);
                    }
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

            // Register payment methods
            if ($hasMultipleMethods) {
                foreach ($paymentMethods as $pm) {
                    $venta->metodoPagos()->create([
                        'tipo_pago' => $pm['tipo_pago'],
                        'monto' => $pm['monto'],
                    ]);
                }
            } else {
                $venta->metodoPagos()->create(['tipo_pago' => $tipoPago]);
            }

            if ($isCredito) {
                // Registrar cuotas
                $subMontoCuota = round($totalFinal / $nroCuotas, 2);
                for ($i = 1; $i <= $nroCuotas; $i++) {
                    $montoCuota = ($i === $nroCuotas) ? ($totalFinal - ($subMontoCuota * ($nroCuotas - 1))) : $subMontoCuota;
                    $venta->ventaCuotas()->create([
                        'sub_monto' => $montoCuota,
                        'nro_cuota' => $i,
                        'estado' => 'pendiente',
                    ]);
                }
            } else {
                if ($hasMultipleMethods) {
                    foreach ($paymentMethods as $pm) {
                        $tipo = $pm['tipo_pago'];
                        $monto = (float) $pm['monto'];
                        $caja->movimientoCajas()->create([
                            'monto' => $monto,
                            'tipo' => 'venta',
                            'detalle' => "Venta #{$venta->id} ({$tipo})",
                        ]);
                        $this->actualizarTotalesSistema($caja, $tipo, $monto);
                    }
                    $caja->increment('monto_sistema', $totalFinal);
                } else {
                    $caja->movimientoCajas()->create([
                        'monto' => $totalFinal,
                        'tipo' => 'venta',
                        'detalle' => "Venta #{$venta->id} ({$tipoPago})",
                    ]);
                    $caja->increment('monto_sistema', $totalFinal);
                    $this->actualizarTotalesSistema($caja, $tipoPago, $totalFinal);
                }
            }

            $this->logSuccess($user, $venta);

            return $venta->load(['detalleVentas.producto', 'cliente', 'ventaCuotas']);
        });
    }

    /**
     * Registrar intento fallido de venta en la bitácora.
     */
    private function actualizarTotalesSistema(AperturaCaja $caja, string $tipoPago, float $monto): void
    {
        $mapa = [
            'efectivo' => 'efectivo',
            'compra_directa' => 'efectivo',
            'qr' => 'qr',
            'tarjeta' => 'tarjeta',
            'credito' => 'credito',
        ];

        $clave = $mapa[$tipoPago] ?? 'efectivo';
        $totales = $caja->totales_sistema;
        $totales[$clave] = ($totales[$clave] ?? 0) + $monto;
        $caja->updateQuietly(['totales_sistema' => $totales]);
    }

    private function logFailure(User $user, string $reason): void
    {
        ActivityLog::create([
            'event_type' => 'sale_failed',
            'user_id' => $user->id,
            'user_identity' => $user->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'resource_name' => 'Ventas',
            'visited_url' => request()->getRequestUri(),
            'description' => "Venta fallida. Motivo: {$reason}.",
        ]);
    }

    /**
     * Registrar registro exitoso de venta en la bitácora.
     */
    private function logSuccess(User $user, Venta $venta): void
    {
        $clienteName = $venta->cliente ? $venta->cliente->name : 'Consumidor Final';
        $detallePago = $venta->tipo_pago === 'credito'
            ? "al crédito ({$venta->nro_cuotas} cuotas)"
            : "al contado ({$venta->tipo_pago})";

        ActivityLog::create([
            'event_type' => 'sale_created',
            'user_id' => $user->id,
            'user_identity' => $user->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'resource_name' => 'Ventas',
            'visited_url' => request()->getRequestUri(),
            'description' => "Venta #{$venta->id} registrada exitosamente por {$venta->monto_final} Bs {$detallePago}. Cliente: {$clienteName}.",
        ]);
    }
}
