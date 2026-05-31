<?php

namespace App\Http\Controllers;

use App\Http\Requests\Venta\StoreVentaRequest;
use App\Services\CajaService;
use App\Services\VentaService;
use Illuminate\Http\RedirectResponse;

class VentaController extends Controller
{
    public function store(StoreVentaRequest $request, CajaService $cajaService, VentaService $ventaService): RedirectResponse
    {
        $caja = $cajaService->activeCaja($request->user());

        if (! $caja) {
            return back()->with('error', 'Debes abrir caja antes de vender.');
        }

        $ventaService->create($request->validated(), $request->user(), $caja);

        return back()->with('success', 'Venta registrada correctamente.');
    }
}
