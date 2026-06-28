<?php

namespace App\Http\Controllers;

use App\Http\Requests\Compra\StoreCompraRequest;
use App\Http\Requests\Compra\UpdateCompraRequest;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\CompraService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompraController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Compras/Index', [
            'compras' => Compra::query()
                ->with(['proveedor', 'detalleCompras.producto', 'detalleCompras.lote'])
                ->latest()
                ->paginate(15),
            'productos' => Producto::query()
                ->with('stockActual')
                ->orderBy('nombre')
                ->get(),
            'proveedores' => Proveedor::query()
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function store(StoreCompraRequest $request, CompraService $service): RedirectResponse
    {
        $service->create($request->validated(), auth()->user());

        return back()->with('success', 'Compra registrada correctamente.');
    }

    public function update(UpdateCompraRequest $request, Compra $compra, CompraService $service): RedirectResponse
    {
        $service->update($compra, $request->validated(), auth()->user());

        return back()->with('success', 'Compra actualizada correctamente.');
    }

    public function destroy(Compra $compra, CompraService $service): RedirectResponse
    {
        $service->delete($compra, auth()->user());

        return back()->with('success', 'Compra eliminada correctamente.');
    }
}
