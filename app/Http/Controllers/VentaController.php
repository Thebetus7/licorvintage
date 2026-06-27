<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Promocion;
use App\Models\User;
use App\Services\CajaService;
use App\Services\VentaService;
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
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|integer|exists:productos,id',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'cliente_id' => 'nullable|integer|exists:users,id',
            'codigo_promo' => 'nullable|string',
            'nro_cuotas' => 'nullable|integer|min:1',
            'qr_transaction_id' => 'nullable',
            'card_number' => 'required_if:tipo_pago,tarjeta|string|nullable',
            'card_expiry' => 'required_if:tipo_pago,tarjeta|string|nullable',
            'card_cvc' => 'required_if:tipo_pago,tarjeta|string|nullable',
        ], [
            'detalles.required' => 'El carrito no puede estar vacío.',
            'detalles.min' => 'El carrito debe contener al menos un producto.',
            'detalles.*.producto_id.exists' => 'Uno de los productos seleccionados no existe.',
            'detalles.*.cantidad.min' => 'La cantidad mínima por producto es 1.',
        ]);

        $ventaService->create($validated, $user, $caja);

        return back()->with('success', 'Venta registrada correctamente.');
    }
}
