<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComprobanteController extends Controller
{
    public function index(Request $request): Response
    {
        $user = auth()->user();

        $query = Venta::with(['cliente', 'detalleVentas.producto', 'metodoPagos', 'ventaCuotas', 'user']);

        if ($user->hasRole('vendedor')) {
            $query->where('user_id', $user->id);
        }

        $from = $request->input('from', today()->toDateString());
        $to = $request->input('to', today()->toDateString());

        $query->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        $ventas = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return Inertia::render('Comprobantes/Index', [
            'ventas' => $ventas,
            'filters' => [
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }
}
