<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Venta;
use App\Models\VentaCuotas;
use App\Services\CajaService;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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

    public function comprobantes(Request $request): JsonResponse
    {
        $user = auth()->user();
        $from = $request->input('from', today()->toDateString());
        $to = $request->input('to', today()->toDateString());

        $ventas = Venta::with(['detalleVentas.producto', 'metodoPagos', 'ventaCuotas'])
            ->where('cliente_id', $user->id)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($ventas);
    }

    public function pedidos(Request $request): JsonResponse
    {
        $user = auth()->user();

        $pedidos = Venta::with(['detalleVentas.producto', 'metodoPagos', 'user'])
            ->where('cliente_id', $user->id)
            ->whereNotNull('estado_pedido')
            ->whereIn('estado_pedido', ['pagado', 'enviado'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($pedidos);
    }

    public function completarPedido(Venta $venta): JsonResponse
    {
        $user = auth()->user();

        if ($venta->cliente_id !== $user->id) {
            throw new AccessDeniedHttpException('Este pedido no te pertenece.');
        }

        $venta->update(['estado_pedido' => 'completado']);

        return response()->json(['success' => true, 'message' => 'Pedido completado.']);
    }
}
