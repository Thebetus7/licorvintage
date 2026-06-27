<?php

namespace App\Http\Controllers;

use App\Services\CajaService;
use App\Services\VentaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VentaController extends Controller
{
    /**
     * Store a newly created sale in storage.
     */
    public function store(Request $request, VentaService $ventaService, CajaService $cajaService): RedirectResponse
    {
        $user = $request->user();

        // Si el usuario es cliente, usa la caja activa del sistema (del vendedor)
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
