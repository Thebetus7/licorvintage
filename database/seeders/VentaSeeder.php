<?php

namespace Database\Seeders;

use App\Models\AperturaCaja;
use App\Models\DetalleVenta;
use App\Models\Lote;
use App\Models\MetodoPago;
use App\Models\MovimientoCaja;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Stock;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaCuotas;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VentaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Cargar todos los datos base en memoria
        $vendedores = User::whereHas('roles', fn ($q) => $q->where('name', 'vendedor'))->get();
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'propietario'))->first();
        $clientes = User::whereHas('roles', fn ($q) => $q->where('name', 'cliente'))->get();
        
        // Excluir productos muy caros para cumplir con la regla de ventas de máximo 1200 Bs
        $productos = Producto::where('precio_venta', '<=', 1200.00)->get();
        $promociones = Promocion::with('detallePromos')->get();
        
        // Cargar lotes ordenados por fecha de creación para hacer FIFO en memoria
        $lotesPorProducto = Lote::where('cantidad_actual', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('producto_id');

        $stocksPorProducto = Stock::all()->keyBy('producto_id');

        // Cargar todas las aperturas de caja creadas por el AperturaCajaSeeder
        $aperturasCaja = AperturaCaja::orderBy('tiempo_apertura', 'asc')->get();

        if ($productos->isEmpty() || $aperturasCaja->isEmpty() || !$admin) {
            return;
        }

        // Iniciar transacción de base de datos para máxima velocidad
        DB::beginTransaction();

        try {
            foreach ($aperturasCaja as $apertura) {
                $fechaDia = Carbon::parse($apertura->tiempo_apertura);
                
                // Determinar cuántas ventas hacer hoy (entre 3 y 8)
                $salesCount = rand(3, 8);
                
                // Acumuladores para la caja de este día
                $totalEfectivo = 0.0;
                $totalQr = 0.0;
                $totalTarjeta = 0.0;
                $totalCredito = 0.0;
                $montoSistema = 500.00; // Inicia con el monto inicial de 500 Bs

                for ($s = 0; $s < $salesCount; $s++) {
                    // Elegir un vendedor para esta venta
                    $vendedor = rand(0, 4) === 0 ? $admin : $vendedores->random();
                    // Elegir cliente (80% de probabilidad de tener cliente registrado, 20% consumidor final)
                    $cliente = rand(0, 4) > 0 ? $clientes->random() : null;

                    // Hora aleatoria para la venta
                    $fechaVenta = $fechaDia->copy()->setHour(rand(9, 21))->setMinute(rand(0, 59))->setSecond(rand(0, 59));

                    // Elegir de 1 a 3 productos aleatorios
                    $ventaProductos = $productos->random(rand(1, 3));
                    $detalles = [];
                    $totalOriginal = 0.0;

                    foreach ($ventaProductos as $prod) {
                        // Verificar stock en memoria
                        $stockDisponible = isset($stocksPorProducto[$prod->id]) ? $stocksPorProducto[$prod->id]->stock : 0;
                        if ($stockDisponible <= 2) {
                            continue; // No vender si queda poco stock
                        }

                        $cantidad = rand(1, 2);
                        if ($cantidad > $stockDisponible) {
                            $cantidad = $stockDisponible;
                        }

                        $subtotal = $prod->precio_venta * $cantidad;
                        
                        $detalles[] = [
                            'producto' => $prod,
                            'cantidad' => $cantidad,
                            'precio_original' => $prod->precio_venta,
                            'subtotal' => $subtotal,
                        ];

                        $totalOriginal += $subtotal;
                    }

                    if (empty($detalles)) {
                        continue;
                    }

                    // --- REGLA: Venta Máxima de 1200 Bs ---
                    // Si el total excede los 1200 Bs, quitamos productos o reducimos cantidades hasta cumplir la regla
                    while ($totalOriginal > 1200.00 && count($detalles) > 1) {
                        $eliminado = array_pop($detalles);
                        $totalOriginal -= $eliminado['subtotal'];
                    }
                    if ($totalOriginal > 1200.00 && count($detalles) === 1) {
                        $detalles[0]['cantidad'] = 1;
                        $detalles[0]['subtotal'] = $detalles[0]['precio_original'] * 1;
                        $totalOriginal = $detalles[0]['subtotal'];
                    }

                    // Aplicar descuento por promoción (15% de probabilidad)
                    $promocionAplicada = null;
                    $descuentoTotal = 0.0;

                    if (rand(1, 100) <= 15) {
                        // Buscar una promoción activa en la fecha de la venta
                        $promoActiva = $promociones->first(function ($p) use ($fechaVenta) {
                            return $fechaVenta->between($p->fecha_inicio, $p->fecha_fin);
                        });

                        if ($promoActiva) {
                            foreach ($detalles as &$det) {
                                $enPromo = $promoActiva->detallePromos->contains('producto_id', $det['producto']->id);
                                if ($enPromo) {
                                    $desc = round($det['subtotal'] * ($promoActiva->descuento / 100), 2);
                                    $det['descuento'] = $desc;
                                    $descuentoTotal += $desc;
                                    $promocionAplicada = $promoActiva;
                                } else {
                                    $det['descuento'] = 0.0;
                                }
                            }
                        }
                    }

                    $montoFinal = $totalOriginal - $descuentoTotal;

                    // Elegir método de pago
                    // 50% Efectivo, 20% QR, 20% Tarjeta, 7% Crédito, 3% Múltiple
                    $randPago = rand(1, 100);
                    $tipoPago = 'efectivo';
                    $nroCuotas = 0;
                    $montoPagado = $montoFinal;

                    if ($randPago <= 50) {
                        $tipoPago = 'efectivo';
                        $totalEfectivo += $montoFinal;
                    } elseif ($randPago <= 70) {
                        $tipoPago = 'qr';
                        $totalQr += $montoFinal;
                    } elseif ($randPago <= 90) {
                        $tipoPago = 'tarjeta';
                        $totalTarjeta += $montoFinal;
                    } elseif ($randPago <= 97) {
                        $tipoPago = 'credito';
                        $nroCuotas = rand(2, 4);
                        // Al ser crédito, el pago inicial puede ser 0 o una fracción
                        $montoPagado = rand(0, 1) === 0 ? 0.0 : round($montoFinal / $nroCuotas, 2);
                        $totalCredito += $montoPagado;
                    } else {
                        $tipoPago = 'multiple';
                        $totalEfectivo += round($montoFinal * 0.5, 2);
                        $totalQr += round($montoFinal * 0.5, 2);
                    }

                    // Crear la Venta
                    $venta = Venta::create([
                        'monto_pagado' => $montoPagado,
                        'monto_original' => $totalOriginal,
                        'monto_final' => $montoFinal,
                        'tipo_pago' => $tipoPago,
                        'nro_cuotas' => $nroCuotas,
                        'user_id' => $vendedor->id,
                        'cliente_id' => $cliente ? $cliente->id : null,
                        'estado_pedido' => 'completado',
                        'created_at' => $fechaVenta,
                        'updated_at' => $fechaVenta,
                    ]);

                    // Crear los detalles de venta y realizar FIFO de lotes
                    foreach ($detalles as $det) {
                        $prod = $det['producto'];
                        $cantAVender = $det['cantidad'];

                        DetalleVenta::create([
                            'cantidad' => $cantAVender,
                            'precio_original' => $det['precio_original'],
                            'descuento' => $det['descuento'] ?? 0.0,
                            'precio_u_final' => $det['precio_original'] - (($det['descuento'] ?? 0.0) / $cantAVender),
                            'subtotal' => $det['subtotal'] - ($det['descuento'] ?? 0.0),
                            'venta_id' => $venta->id,
                            'producto_id' => $prod->id,
                            'created_at' => $fechaVenta,
                            'updated_at' => $fechaVenta,
                        ]);

                        // FIFO en lotes en memoria
                        $lotes = isset($lotesPorProducto[$prod->id]) ? $lotesPorProducto[$prod->id] : collect();
                        $restante = $cantAVender;
                        $nuevoStock = isset($stocksPorProducto[$prod->id]) ? ($stocksPorProducto[$prod->id]->stock - $cantAVender) : 0;

                        foreach ($lotes as $lote) {
                            if ($lote->cantidad_actual <= 0) {
                                continue;
                            }

                            if ($lote->cantidad_actual >= $restante) {
                                $lote->cantidad_actual -= $restante;
                                
                                // Registrar movimiento de inventario (Salida)
                                MovimientoInventario::create([
                                    'tipo' => 'salida_venta',
                                    'cantidad' => $restante,
                                    'costo_unitario' => $prod->costo,
                                    'saldo_cantidad' => $nuevoStock,
                                    'saldo_costo_promedio' => $prod->costo,
                                    'motivo' => 'Venta #' . $venta->id,
                                    'producto_id' => $prod->id,
                                    'lote_id' => $lote->id,
                                    'user_id' => $vendedor->id,
                                    'created_at' => $fechaVenta,
                                    'updated_at' => $fechaVenta,
                                ]);

                                $restante = 0;
                                break;
                            } else {
                                $deducir = $lote->cantidad_actual;
                                $restante -= $deducir;
                                $lote->cantidad_actual = 0;

                                // Registrar movimiento de inventario (Salida)
                                MovimientoInventario::create([
                                    'tipo' => 'salida_venta',
                                    'cantidad' => $deducir,
                                    'costo_unitario' => $prod->costo,
                                    'saldo_cantidad' => $nuevoStock,
                                    'saldo_costo_promedio' => $prod->costo,
                                    'motivo' => 'Venta #' . $venta->id,
                                    'producto_id' => $prod->id,
                                    'lote_id' => $lote->id,
                                    'user_id' => $vendedor->id,
                                    'created_at' => $fechaVenta,
                                    'updated_at' => $fechaVenta,
                                ]);
                            }
                        }

                        // Actualizar Stock en memoria
                        if (isset($stocksPorProducto[$prod->id])) {
                            $stocksPorProducto[$prod->id]->stock -= $cantAVender;
                        }
                    }

                    // Crear registros de Métodos de Pago
                    if ($tipoPago === 'multiple') {
                        MetodoPago::create([
                            'tipo_pago' => 'efectivo',
                            'monto' => round($montoFinal * 0.5, 2),
                            'venta_id' => $venta->id,
                            'created_at' => $fechaVenta,
                        ]);
                        MetodoPago::create([
                            'tipo_pago' => 'qr',
                            'monto' => round($montoFinal * 0.5, 2),
                            'venta_id' => $venta->id,
                            'created_at' => $fechaVenta,
                        ]);
                    } else {
                        MetodoPago::create([
                            'tipo_pago' => $tipoPago,
                            'monto' => $montoFinal,
                            'venta_id' => $venta->id,
                            'created_at' => $fechaVenta,
                        ]);
                    }

                    // Si es crédito, crear las cuotas correspondientes
                    if ($tipoPago === 'credito') {
                        $montoRestante = $montoFinal - $montoPagado;
                        $montoCuota = round($montoRestante / $nroCuotas, 2);

                        for ($c = 1; $c <= $nroCuotas; $c++) {
                            $fechaVencimientoCuota = $fechaVenta->copy()->addMonths($c);
                            // Si la fecha de vencimiento es antes de la fecha final del seeder, simular cobrada
                            $yaPagada = $fechaVencimientoCuota->lte(Carbon::create(2026, 6, 29));
                            
                            VentaCuotas::create([
                                'sub_monto' => $montoCuota,
                                'nro_cuota' => $c,
                                'venta_id' => $venta->id,
                                'estado' => $yaPagada ? 'pagado' : 'pendiente',
                                'fecha_pago' => $yaPagada ? $fechaVencimientoCuota->copy()->subDays(rand(0, 5)) : null,
                                'created_at' => $fechaVenta,
                                'updated_at' => $fechaVenta,
                            ]);

                            if ($yaPagada) {
                                $venta->monto_pagado += $montoCuota;
                                $venta->save();
                            }
                        }
                    }

                    // Registrar Movimiento de Caja (Ingreso por venta)
                    MovimientoCaja::create([
                        'monto' => $montoPagado,
                        'tipo' => 'ingreso',
                        'detalle' => 'Venta #' . $venta->id,
                        'apertura_caja_id' => $apertura->id,
                        'created_at' => $fechaVenta,
                        'updated_at' => $fechaVenta,
                    ]);
                }

                // --- CIERRE DE CAJA DIARIO ---
                $montoSistema += $totalEfectivo; // La caja física solo recibe efectivo
                
                // Simular discrepancia de caja (+/- 5 Bs con 5% de probabilidad)
                $diferencia = 0.0;
                if (rand(1, 100) <= 5) {
                    $diferencia = rand(-50, 50) / 10;
                }
                $montoReal = $montoSistema + $diferencia;

                $apertura->update([
                    'monto_sistema' => $montoSistema,
                    'monto_real' => $montoReal,
                    'diferencia' => $diferencia,
                    'totales_sistema' => [
                        'efectivo' => $totalEfectivo,
                        'qr' => $totalQr,
                        'tarjeta' => $totalTarjeta,
                        'credito' => $totalCredito,
                    ],
                    'totales_caja' => [
                        'efectivo' => $totalEfectivo + $diferencia,
                        'qr' => $totalQr,
                        'tarjeta' => $totalTarjeta,
                        'credito' => $totalCredito,
                    ],
                ]);
            }

            // 4. Guardar los lotes y stocks actualizados en la base de datos
            foreach ($lotesPorProducto as $prodId => $lotes) {
                foreach ($lotes as $lote) {
                    $lote->save();
                }
            }

            foreach ($stocksPorProducto as $stock) {
                $stock->save();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
