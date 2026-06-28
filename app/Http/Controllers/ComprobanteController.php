<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComprobanteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Venta::with(['cliente', 'detalleVentas.producto', 'metodoPagos', 'ventaCuotas', 'user']);

        $from = $request->input('from', today()->toDateString());
        $to = $request->input('to', today()->toDateString());

        $query->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        $ventas = $query->orderByDesc('created_at')->paginate(20);

        return response()->json($ventas);
    }
}
