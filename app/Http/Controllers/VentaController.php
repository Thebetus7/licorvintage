<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Promocion;
use App\Models\User;
use App\Models\Venta;
use App\Services\CajaService;
use App\Services\VentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VentaController extends Controller
{
    public function index(CajaService $cajaService): Response
    {
        $user = auth()->user();
        $cajaActiva = $cajaService->activeCaja($user) ?? $cajaService->activeCajaDelSistema();

        return Inertia::render('Ventas/Index', [
            'cajaActiva' => $cajaActiva?->load('user', 'opener'),
            'productos' => Producto::query()
                ->with('stockActual')
                ->where('publicado', true)
                ->whereHas('stockActual', fn ($q) => $q->where('stock', '>', 0))
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'mililitros', 'precio_venta', 'codigo_barra', 'codigo_qr', 'imagen']),
            'promocionesActive' => Promocion::whereDate('fecha_inicio', '<=', today())
                ->whereDate('fecha_fin', '>=', today())
                ->orderBy('nombre_promo')
                ->get(),
            'clientes' => User::role('cliente')
                ->orderBy('name')
                ->get(['id', 'name', 'ci', 'email', 'phone']),
        ]);
    }

    public function store(Request $request, VentaService $ventaService, CajaService $cajaService): RedirectResponse
    {
        $user = $request->user();

        $caja = $cajaService->activeCaja($user) ?? $cajaService->activeCajaDelSistema();

        abort_unless($caja, 403, 'No hay ninguna caja abierta en el sistema para registrar ventas.');

        $validated = $request->validate([
            'tipo_pago' => 'required|string',
            'monto_pagado' => 'nullable|numeric',
            'payment_methods' => 'nullable|array',
            'payment_methods.*.tipo_pago' => 'required_with:payment_methods|string',
            'payment_methods.*.monto' => 'required_with:payment_methods|numeric|min:0',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|integer|exists:productos,id',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'cliente_id' => 'nullable|integer|exists:users,id',
            'codigo_promo' => 'nullable|string',
            'nro_cuotas' => 'nullable|integer|min:1',
            'qr_transaction_id' => 'nullable',
            'card_number' => 'nullable|string',
            'card_expiry' => 'nullable|string',
            'card_cvc' => 'nullable|string',
        ], [
            'detalles.required' => 'El carrito no puede estar vacío.',
            'detalles.min' => 'El carrito debe contener al menos un producto.',
            'detalles.*.producto_id.exists' => 'Uno de los productos seleccionados no existe.',
            'detalles.*.cantidad.min' => 'La cantidad mínima por producto es 1.',
        ]);

        if ($user->hasRole('cliente')) {
            $validated['estado_pedido'] = 'pagado';
        }

        $venta = $ventaService->create($validated, $user, $caja);

        return back()->with([
            'success' => 'Venta registrada correctamente.',
            'venta_id' => $venta->id,
        ]);
    }

    public function pedidos(Request $request): JsonResponse
    {
        $today = today()->toDateString();
        $from = $request->input('from', $today);
        $to = $request->input('to', $today);

        $pedidos = Venta::with(['cliente', 'detalleVentas.producto', 'user'])
            ->whereNotNull('estado_pedido')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($pedidos);
    }

    public function updateEstadoPedido(Request $request, Venta $venta): RedirectResponse
    {
        $request->validate(['estado' => 'required|string|in:enviado']);

        $venta->update(['estado_pedido' => $request->estado]);

        return back()->with('success', 'Pedido actualizado a enviado.');
    }
}
