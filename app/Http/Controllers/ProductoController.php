<?php

namespace App\Http\Controllers;

use App\Http\Requests\Producto\StoreProductoRequest;
use App\Http\Requests\Producto\UpdateProductoRequest;
use App\Models\Producto;
use App\Services\ProductoService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Productos/Index', [
            'productos' => Producto::query()
                ->with('stockActual')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function store(StoreProductoRequest $request, ProductoService $service): RedirectResponse
    {
        $service->create($request->validated(), auth()->user());

        return back()->with('success', 'Producto creado correctamente.');
    }

    public function update(UpdateProductoRequest $request, Producto $producto, ProductoService $service): RedirectResponse
    {
        $service->update($producto, $request->validated());

        return back()->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        $nombre = $producto->nombre;
        $codigo = $producto->codigo_barra;
        $id = $producto->id;

        $producto->delete();

        \App\Models\ActivityLog::create([
            'event_type' => 'product_deleted',
            'user_id' => auth()->id(),
            'user_identity' => auth()->user()?->email ?? 'sistema',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'resource_name' => 'Productos',
            'visited_url' => request()->getRequestUri(),
            'description' => "Producto eliminado: {$nombre} (ID: {$id}) - Código: {$codigo}.",
        ]);

        return back()->with('success', 'Producto eliminado correctamente.');
    }
}
