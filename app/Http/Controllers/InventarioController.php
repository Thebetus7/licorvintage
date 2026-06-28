<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inventario\StoreConteoRequest;
use App\Http\Requests\Inventario\StoreSalidaRequest;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Services\InventarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventarioController extends Controller
{
    public function index(InventarioService $inventarioService): Response
    {
        $totalProximos = \App\Models\Lote::query()
            ->where('estado', 'activo')
            ->where('fecha_expiracion', '<=', now()->addDays(30))
            ->where('fecha_expiracion', '>', now())
            ->count();

        $totalVencidos = \App\Models\Lote::query()
            ->where('estado', 'activo')
            ->where('fecha_expiracion', '<=', now())
            ->count();

        return Inertia::render('Inventario/Index', [
            'valorTotal' => $inventarioService->valorizacionTotal(),
            'productosBajoMinimo' => $inventarioService->productosBajoMinimo(),
            'ultimosMovimientos' => MovimientoInventario::query()
                ->with(['producto', 'user'])
                ->latest()
                ->limit(10)
                ->get(),
            'totalProximos' => $totalProximos,
            'totalVencidos' => $totalVencidos,
            'productos' => Producto::query()->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function movimientos(Request $request): Response
    {
        $movimientos = MovimientoInventario::query()
            ->with(['producto', 'user'])
            ->when($request->producto_id, fn ($query) => $query->where('producto_id', $request->producto_id))
            ->when($request->tipo, fn ($query) => $query->where('tipo', $request->tipo))
            ->when($request->desde, fn ($query) => $query->whereDate('created_at', '>=', $request->desde))
            ->when($request->hasta, fn ($query) => $query->whereDate('created_at', '<=', $request->hasta))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Inventario/Movimientos', [
            'movimientos' => $movimientos,
            'productos' => Producto::query()->orderBy('nombre')->get(['id', 'nombre']),
            'filters' => $request->only(['producto_id', 'tipo', 'desde', 'hasta']),
            'tipos' => $this->tiposMovimiento(),
        ]);
    }

    public function kardex(Request $request): Response
    {
        $productoId = $request->integer('producto_id') ?: null;

        $movimientos = collect();

        if ($productoId) {
            $movimientos = MovimientoInventario::query()
                ->with('user')
                ->where('producto_id', $productoId)
                ->when($request->desde, fn ($query) => $query->whereDate('created_at', '>=', $request->desde))
                ->when($request->hasta, fn ($query) => $query->whereDate('created_at', '<=', $request->hasta))
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();
        }

        return Inertia::render('Inventario/Kardex', [
            'movimientos' => $movimientos,
            'productos' => Producto::query()->with('stockActual')->orderBy('nombre')->get(),
            'filters' => $request->only(['producto_id', 'desde', 'hasta']),
        ]);
    }

    public function valorizacion(InventarioService $inventarioService): Response
    {
        $productos = Producto::query()
            ->with('stockActual')
            ->orderBy('nombre')
            ->get()
            ->map(fn (Producto $producto) => [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'codigo_barra' => $producto->codigo_barra,
                'stock' => $producto->stockActual?->stock ?? 0,
                'costo_promedio' => (float) $producto->costo_promedio,
                'valor_total' => ($producto->stockActual?->stock ?? 0) * (float) $producto->costo_promedio,
            ]);

        return Inertia::render('Inventario/Valorizacion', [
            'productos' => $productos,
            'valorTotal' => $inventarioService->valorizacionTotal(),
        ]);
    }




    public function lotes(Request $request): Response
    {
        $lotes = \App\Models\Lote::query()
            ->with('producto')
            ->when($request->producto_id, fn ($query) => $query->where('producto_id', $request->producto_id))
            ->when($request->estado, fn ($query) => $query->where('estado', $request->estado))
            ->orderByRaw('fecha_expiracion ASC NULLS LAST')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $totalProximos = \App\Models\Lote::query()
            ->where('estado', 'activo')
            ->where('fecha_expiracion', '<=', now()->addDays(30))
            ->where('fecha_expiracion', '>', now())
            ->count();

        $totalVencidos = \App\Models\Lote::query()
            ->where('estado', 'activo')
            ->where('fecha_expiracion', '<=', now())
            ->count();

        return Inertia::render('Inventario/Lotes', [
            'lotes' => $lotes,
            'productos' => Producto::query()->orderBy('nombre')->get(['id', 'nombre']),
            'filters' => $request->only(['producto_id', 'estado']),
            'totalProximos' => $totalProximos,
            'totalVencidos' => $totalVencidos,
        ]);
    }

    public function storeSalida(StoreSalidaRequest $request, InventarioService $inventarioService): RedirectResponse
    {
        $inventarioService->registrarNotaSalida($request->validated(), $request->user());

        return back()->with('success', 'Nota de salida registrada correctamente.');
    }

    public function salidas(Request $request): Response
    {
        $salidas = \App\Models\NotaSalida::query()
            ->with(['tipoSalida', 'user', 'detalleSalidas.producto'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Inventario/Salidas', [
            'salidas' => $salidas,
            'tiposSalida' => \App\Models\TipoSalida::all(),
            'productos' => Producto::query()->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function conteo(): Response
    {
        return Inertia::render('Inventario/Conteo', [
            'productos' => Producto::query()
                ->with('stockActual')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function guardarConteo(StoreConteoRequest $request, InventarioService $inventarioService): RedirectResponse
    {
        $ajustes = 0;
        $motivo = $request->validated()['motivo'] ?? 'Ajuste por conteo';

        foreach ($request->validated()['conteos'] as $conteo) {
            $producto = Producto::query()->findOrFail($conteo['producto_id']);
            $ajuste = $inventarioService->ajustarPorConteo(
                $producto,
                (int) $conteo['stock_fisico'],
                $request->user(),
                $motivo
            );

            if ($ajuste) {
                $ajustes++;
            }
        }

        return back()->with('success', "Conteo guardado. {$ajustes} ajuste(s) aplicado(s) en base a diferencias de stock.");
    }

    private function tiposMovimiento(): array
    {
        return [
            'ingreso_compra',
            'ingreso_devolucion',
            'ingreso_ajuste',
            'ingreso_inicial',
            'salida_venta',
            'salida_merma',
            'salida_ajuste',
        ];
    }
}
