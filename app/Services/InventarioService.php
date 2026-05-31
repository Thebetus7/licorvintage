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
    ): MovimientoInventario {
        return DB::transaction(function () use ($producto, $cantidad, $costoUnitario, $tipo, $referencia, $user, $motivo): MovimientoInventario {
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
    ): MovimientoInventario {
        return DB::transaction(function () use ($producto, $cantidad, $tipo, $referencia, $user, $motivo): MovimientoInventario {
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
            ]);
        });
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
                $this->registrarSalida(
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
