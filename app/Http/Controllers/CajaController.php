<?php

namespace App\Http\Controllers;

use App\Http\Requests\Caja\CloseCajaRequest;
use App\Http\Requests\Caja\OpenCajaRequest;
use App\Models\AperturaCaja;
use App\Models\Producto;
use App\Models\User;
use App\Models\Promocion;
use App\Models\Venta;
use App\Models\VentaCuotas;
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
            'clientes' => User::role('cliente')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'promocionesActive' => Promocion::whereDate('fecha_inicio', '<=', today())
                ->whereDate('fecha_fin', '>=', today())
                ->orderBy('nombre_promo')
                ->get(),
            'creditosPendientes' => Venta::with(['cliente', 'ventaCuotas' => fn($q) => $q->orderBy('nro_cuota')])
                ->where('tipo_pago', 'credito')
                ->whereHas('ventaCuotas', fn($q) => $q->where('estado', 'pendiente'))
                ->orderByDesc('created_at')
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

    /**
     * Pay a specific credit installment.
     */
    public function payInstallment(VentaCuotas $cuota, CajaService $service): RedirectResponse
    {
        $user = auth()->user();
        $caja = $service->activeCaja($user);

        abort_unless($caja, 403, 'Debes tener una caja abierta para cobrar cuotas.');

        $cuota->update([
            'estado' => 'pagado',
            'fecha_pago' => now(),
        ]);

        $caja->movimientoCajas()->create([
            'monto' => $cuota->sub_monto,
            'tipo' => 'ingreso',
            'detalle' => "Cobro Cuota #{$cuota->nro_cuota} de Venta #{$cuota->venta_id}",
        ]);

        $caja->increment('monto_sistema', $cuota->sub_monto);

        return back()->with('success', "Cuota #{$cuota->nro_cuota} cobrada correctamente.");
    }
}
