<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Search products by name, barcode, or description.
     */
    public function quickSearch(Request $request)
    {
        $query = $request->input('query');

        if (empty($query)) {
            return response()->json([]);
        }

        $productos = Producto::with('stockActual')
            ->where(function ($q) use ($query) {
                $q->where('nombre', 'like', "%{$query}%")
                  ->orWhere('codigo_barra', 'like', "%{$query}%")
                  ->orWhere('descripcion', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get();

        return response()->json($productos->map(function ($producto) {
            return [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'mililitros' => $producto->mililitros,
                'precio_venta' => $producto->precio_venta,
                'descripcion' => $producto->descripcion,
                'imagen' => $producto->imagen,
                'codigo_barra' => $producto->codigo_barra,
                'codigo_qr' => $producto->codigo_qr,
                'stock' => $producto->stockActual?->stock ?? 0,
            ];
        }));
    }
}
