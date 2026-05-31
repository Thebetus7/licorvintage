<?php

namespace App\Http\Controllers;

use App\Http\Requests\Caja\CloseCajaRequest;
use App\Http\Requests\Caja\OpenCajaRequest;
use App\Models\AperturaCaja;
use App\Models\Producto;
use App\Services\CajaService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CajaController extends Controller
{
    public function index(CajaService $service): Response
    {
        return Inertia::render('Caja/Index', [
            'cajaActiva' => $service->activeCaja(auth()->user())?->load('movimientoCajas'),
            'productos' => Producto::query()
                ->with('stockActual')
                ->whereHas('stockActual', fn ($query) => $query->where('stock', '>', 0))
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function open(OpenCajaRequest $request, CajaService $service): RedirectResponse
    {
        $service->open($request->user(), (float) $request->validated('monto_inicial'));

        return back()->with('success', 'Caja abierta correctamente.');
    }

    public function close(CloseCajaRequest $request, AperturaCaja $caja, CajaService $service): RedirectResponse
    {
        abort_unless($caja->user_id === $request->user()->id || $request->user()->hasRole('propietario'), 403);

        $service->close($caja, (float) $request->validated('monto_real'));

        return back()->with('success', 'Caja cerrada correctamente.');
    }
}
