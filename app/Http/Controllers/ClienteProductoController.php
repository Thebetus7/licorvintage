<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Inertia\Inertia;
use Inertia\Response;

class ClienteProductoController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Cliente/Productos', [
            'productos' => Producto::query()
                ->with('stockActual')
                ->where('publicado', true)
                ->whereHas('stockActual', fn ($query) => $query->where('stock', '>', 0))
                ->orderBy('nombre')
                ->get(),
        ]);
    }
}
