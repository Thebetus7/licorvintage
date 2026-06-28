<?php

namespace App\Services;

use App\Models\AjusteInventario;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventarioService
{
    public function registrarIngreso(
        Producto $producto,
        int $cantidad,
        float $costoUnitario,
        string $tipo,
        ?Model $referencia,
        User $user,
        ?string $motivo = null,
        ?string $fechaExpiracion = null,
        ?int $loteId = null,
        ?int $proveedorId = null,
    ): MovimientoInventario {
        return DB::transaction(function () use ($producto, $cantidad, $costoUnitario, $tipo, $referencia, $user, $motivo, $fechaExpiracion, $loteId, $proveedorId): MovimientoInventario {
            $producto = Producto::query()->lockForUpdate()->findOrFail($producto->id);
            $stock = $this->obtenerStock($producto);
            $saldoActual = $stock->stock;
            $costoPromedioActual = (float) $producto->costo_promedio;

            $nuevoCostoPromedio = $this->recalcularCostoPromedio($producto, $cantidad, $costoUnitario);
            $nuevoSaldo = $saldoActual + $cantidad;

            $stock->increment('stock', $cantidad);

            $producto->update([
                'costo_promedio' => $nuevoCostoPromedio,
                'costo' => $costoUnitario,
            ]);

            $lote = null;
            if ($loteId) {
                $lote = \App\Models\Lote::findOrFail($loteId);
            } else {
                $lote = \App\Models\Lote::create([
                    'producto_id' => $producto->id,
                    'compra_id' => $referencia instanceof \App\Models\Compra ? $referencia->id : null,
                    'proveedor_id' => $proveedorId,
                    'cantidad_inicial' => $cantidad,
                    'cantidad_actual' => $cantidad,
                    'fecha_ingreso' => today(),
                    'fecha_expiracion' => $fechaExpiracion,
                    'estado' => 'activo',
                ]);
            }

            return MovimientoInventario::create([
                'producto_id' => $producto->id,
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnitario,
                'saldo_cantidad' => $nuevoSaldo,
                'saldo_costo_promedio' => $nuevoCostoPromedio,
                'referencia_type' => $referencia ? $referencia->getMorphClass() : null,
                'referencia_id' => $referencia?->getKey(),
                'motivo' => $motivo,
                'user_id' => $user->id,
                'lote_id' => $lote->id,
            ]);
        });
    }

    public function registrarSalida(
        Producto $producto,
        int $cantidad,
        string $tipo,
        ?Model $referencia,
        User $user,
        ?string $motivo = null,
        ?int $loteId = null,
    ): MovimientoInventario {
        return DB::transaction(function () use ($producto, $cantidad, $tipo, $referencia, $user, $motivo, $loteId): MovimientoInventario {
            $producto = Producto::query()->lockForUpdate()->findOrFail($producto->id);
            $stock = $this->obtenerStock($producto);

            if ($stock->stock < $cantidad) {
                throw ValidationException::withMessages([
                    'cantidad' => "Stock insuficiente para {$producto->nombre}.",
                ]);
            }

            $costoUnitario = (float) $producto->costo_promedio;
            $nuevoSaldo = $stock->stock - $cantidad;

            $stock->decrement('stock', $cantidad);

            return MovimientoInventario::create([
                'producto_id' => $producto->id,
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnitario,
                'saldo_cantidad' => $nuevoSaldo,
                'saldo_costo_promedio' => $costoUnitario,
                'referencia_type' => $referencia ? $referencia->getMorphClass() : null,
                'referencia_id' => $referencia?->getKey(),
                'motivo' => $motivo,
                'user_id' => $user->id,
                'lote_id' => $loteId,
            ]);
        });
    }

    public function descontarStockDeLotes(
        Producto $producto,
        int $cantidad,
        string $tipo,
        ?Model $referencia,
        User $user,
        ?string $motivo = null
    ): void {
        $cantidadRestante = $cantidad;

        $lotes = \App\Models\Lote::query()
            ->where('producto_id', $producto->id)
            ->where('cantidad_actual', '>', 0)
            ->where('estado', 'activo')
            ->where(fn ($q) => $q->whereNull('fecha_expiracion')->orWhere('fecha_expiracion', '>', now()))
            ->orderByRaw('fecha_expiracion ASC NULLS LAST')
            ->orderBy('id', 'ASC')
            ->lockForUpdate()
            ->get();

        foreach ($lotes as $lote) {
            if ($cantidadRestante <= 0) {
                break;
            }

            $aDescontar = min($lote->cantidad_actual, $cantidadRestante);
            
            $lote->decrement('cantidad_actual', $aDescontar);
            if ($lote->cantidad_actual <= 0) {
                $lote->update(['estado' => 'agotado']);
            }

            $this->registrarSalida(
                $producto,
                $aDescontar,
                $tipo,
                $referencia,
                $user,
                $motivo . " (Lote: {$lote->codigo_lote})",
                $lote->id
            );

            $cantidadRestante -= $aDescontar;
        }

        if ($cantidadRestante > 0) {
            $this->registrarSalida(
                $producto,
                $cantidadRestante,
                $tipo,
                $referencia,
                $user,
                $motivo . " (Sin lote disponible)",
                null
            );
        }
    }

    public function recalcularCostoPromedio(Producto $producto, int $cantIngresada, float $costoIngreso): float
    {
        $stock = $this->obtenerStock($producto);
        $saldoActual = $stock->stock;
        $costoPromedioActual = (float) $producto->costo_promedio;

        if ($saldoActual + $cantIngresada <= 0) {
            return $costoIngreso;
        }

        if ($saldoActual <= 0) {
            return $costoIngreso;
        }

        return (($saldoActual * $costoPromedioActual) + ($cantIngresada * $costoIngreso))
            / ($saldoActual + $cantIngresada);
    }

    public function ajustarPorConteo(Producto $producto, int $stockFisico, User $user, ?string $motivo = null): ?AjusteInventario
    {
        return DB::transaction(function () use ($producto, $stockFisico, $user, $motivo): ?AjusteInventario {
            $producto = Producto::query()->with('stockActual')->findOrFail($producto->id);
            $stockSistema = $producto->stockActual?->stock ?? 0;
            $diferencia = $stockFisico - $stockSistema;

            if ($diferencia === 0) {
                return null;
            }

            $ajuste = AjusteInventario::create([
                'producto_id' => $producto->id,
                'stock_sistema' => $stockSistema,
                'stock_fisico' => $stockFisico,
                'diferencia' => $diferencia,
                'motivo' => $motivo ?? 'Conteo físico',
                'user_id' => $user->id,
            ]);

            if ($diferencia > 0) {
                $this->registrarIngreso(
                    $producto,
                    $diferencia,
                    (float) $producto->costo_promedio,
                    'ingreso_ajuste',
                    $ajuste,
                    $user,
                    $ajuste->motivo,
                );
            } else {
                $this->descontarStockDeLotes(
                    $producto,
                    abs($diferencia),
                    'salida_ajuste',
                    $ajuste,
                    $user,
                    $ajuste->motivo,
                );
            }

            return $ajuste;
        });
    }

    public function valorizacionTotal(): float
    {
        return Producto::query()
            ->with('stockActual')
            ->get()
            ->sum(fn (Producto $producto) => ($producto->stockActual?->stock ?? 0) * (float) $producto->costo_promedio);
    }

    public function productosBajoMinimo(): int
    {
        return Producto::query()
            ->whereHas('stockActual', fn ($query) => $query->whereColumn('stocks.stock', '<', 'stocks.min'))
            ->count();
    }

    public function registrarNotaSalida(array $data, User $user): \App\Models\NotaSalida
    {
        return DB::transaction(function () use ($data, $user): \App\Models\NotaSalida {
            $notaSalida = \App\Models\NotaSalida::create([
                'tipo_salida_id' => $data['tipo_salida_id'],
                'fecha' => $data['fecha'],
                'user_id' => $user->id,
            ]);

            foreach ($data['detalles'] as $detalle) {
                $producto = Producto::findOrFail($detalle['producto_id']);
                $cantidadADescontar = (int) $detalle['cantidad'];
                
                $lotes = \App\Models\Lote::query()
                    ->where('producto_id', $producto->id)
                    ->where('cantidad_actual', '>', 0)
                    ->where('estado', 'activo')
                    ->orderByRaw('fecha_expiracion ASC NULLS LAST')
                    ->orderBy('id', 'ASC')
                    ->lockForUpdate()
                    ->get();

                $cantidadRestante = $cantidadADescontar;

                foreach ($lotes as $lote) {
                    if ($cantidadRestante <= 0) {
                        break;
                    }

                    $aDescontar = min($lote->cantidad_actual, $cantidadRestante);
                    
                    $lote->decrement('cantidad_actual', $aDescontar);
                    if ($lote->cantidad_actual <= 0) {
                        $lote->update(['estado' => 'agotado']);
                    }

                    $notaSalida->detalleSalidas()->create([
                        'producto_id' => $producto->id,
                        'lote_id' => $lote->id,
                        'cantidad' => $aDescontar,
                    ]);

                    $this->registrarSalida(
                        $producto,
                        $aDescontar,
                        'salida_merma',
                        $notaSalida,
                        $user,
                        "Salida por {$notaSalida->tipoSalida->nombre} (Lote: {$lote->codigo_lote})",
                        $lote->id
                    );

                    $cantidadRestante -= $aDescontar;
                }

                if ($cantidadRestante > 0) {
                    $notaSalida->detalleSalidas()->create([
                        'producto_id' => $producto->id,
                        'lote_id' => null,
                        'cantidad' => $cantidadRestante,
                    ]);

                    $this->registrarSalida(
                        $producto,
                        $cantidadRestante,
                        'salida_merma',
                        $notaSalida,
                        $user,
                        "Salida por {$notaSalida->tipoSalida->nombre} (Sin lote disponible)",
                        null
                    );
                }
            }

            return $notaSalida->load(['tipoSalida', 'user', 'detalleSalidas.producto']);
        });
    }

    private function obtenerStock(Producto $producto): Stock
    {
        $stock = Stock::query()
            ->where('producto_id', $producto->id)
            ->lockForUpdate()
            ->latest()
            ->first();

        if (! $stock) {
            throw ValidationException::withMessages([
                'stock' => "El producto {$producto->nombre} no tiene registro de stock.",
            ]);
        }

        return $stock;
    }
}
