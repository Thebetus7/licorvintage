<?php

namespace App\Http\Controllers;

use App\Http\Requests\Caja\CloseCajaRequest;
use App\Http\Requests\Caja\OpenCajaRequest;
use App\Models\AperturaCaja;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaCuotas;
use App\Services\CajaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CajaController extends Controller
{
    public function index(CajaService $service, Request $request): Response
    {
        $user = auth()->user();
        $isPropietario = $user->hasRole('propietario');

        $cajaActiva = $isPropietario
            ? $service->activeCajaDelSistema()
            : $service->activeCaja($user);

        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        $aperturas = AperturaCaja::with(['user', 'opener'])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Caja/Index', [
            'cajaActiva' => $cajaActiva?->load(['user', 'opener', 'movimientoCajas']),
            'vendedores' => User::role('vendedor')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'clientes' => User::role('cliente')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'creditosPendientes' => Venta::with(['cliente', 'ventaCuotas' => fn ($q) => $q->orderBy('nro_cuota')])
                ->where('tipo_pago', 'credito')
                ->whereHas('ventaCuotas', fn ($q) => $q->where('estado', 'pendiente'))
                ->orderByDesc('created_at')
                ->get(),
            'aperturas' => $aperturas,
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function open(OpenCajaRequest $request, CajaService $service): RedirectResponse
    {
        Gate::allowIf(fn ($user) => $user->hasRole('propietario'));

        $service->open(
            $request->user(),
            (int) $request->validated('vendedor_id'),
            (float) $request->validated('monto_inicial')
        );

        return back()->with('success', 'Caja abierta correctamente.');
    }

    public function close(CloseCajaRequest $request, AperturaCaja $caja, CajaService $service): RedirectResponse
    {
        abort_unless($caja->user_id === $request->user()->id, 403, 'Solo el vendedor asignado puede cerrar esta caja.');

        $service->close($caja, $request->validated('totales_caja'));

        return back()->with('success', 'Caja cerrada correctamente.');
    }

    /**
     * Pay a specific credit installment.
     */
    public function payInstallment(Request $request, VentaCuotas $cuota, CajaService $service): RedirectResponse
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:efectivo,qr,tarjeta',
        ]);

        $user = auth()->user();
        $caja = $service->activeCaja($user);

        abort_unless($caja, 403, 'Debes tener una caja abierta para cobrar cuotas.');

        $service->registrarPagoCuota($cuota, $user, $caja, $validated['payment_method']);

        return back()->with('success', "Cuota #{$cuota->nro_cuota} cobrada correctamente ({$validated['payment_method']}).");
    }
}
