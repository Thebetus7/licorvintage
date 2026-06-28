<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Venta;
use App\Models\VentaCuotas;
use App\Services\CajaService;
use App\Services\StripeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClienteProductoController extends Controller
{
    public function __invoke(): Response
    {
        $user = auth()->user();
        $creditos = Venta::with(['cliente', 'ventaCuotas' => fn ($q) => $q->orderBy('nro_cuota')])
            ->where('cliente_id', $user->id)
            ->where('tipo_pago', 'credito')
            ->whereHas('ventaCuotas', fn ($q) => $q->where('estado', 'pendiente'))
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Catalogo/Index', [
            'productos' => Producto::query()
                ->with('stockActual')
                ->where('publicado', true)
                ->whereHas('stockActual', fn ($query) => $query->where('stock', '>', 0))
                ->orderBy('nombre')
                ->get(),
            'promocionesActive' => Promocion::whereDate('fecha_inicio', '<=', today())
                ->whereDate('fecha_fin', '>=', today())
                ->orderBy('nombre_promo')
                ->get(),
            'creditosPendientes' => $creditos,
        ]);
    }
}
