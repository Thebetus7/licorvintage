<?php

namespace App\Http\Controllers;

use App\Http\Requests\Caja\CloseCajaRequest;
use App\Http\Requests\Caja\OpenCajaRequest;
use App\Models\ActivityLog;
use App\Models\AperturaCaja;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaCuotas;
use App\Services\CajaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CajaController extends Controller
{
    public function index(CajaService $service): Response
    {
        $user = auth()->user();
        $isPropietario = $user->hasRole('propietario');

        $cajaActiva = $isPropietario
            ? $service->activeCajaDelSistema()
            : $service->activeCaja($user);

        $comprobantes = collect();
        if ($cajaActiva) {
            $comprobantes = Venta::with(['detalleVentas.producto', 'cliente', 'metodoPagos'])
                ->where('user_id', $cajaActiva->user_id)
                ->whereBetween('created_at', [$cajaActiva->tiempo_apertura, now()])
                ->orderByDesc('created_at')
                ->get();
        }

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
            'comprobantes' => $comprobantes,
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

        ActivityLog::create([
            'event_type' => 'caja_movement',
            'user_id' => $user->id,
            'user_identity' => $user->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'resource_name' => 'Caja',
            'visited_url' => request()->getRequestUri(),
            'description' => "Cobro de cuota realizado: Ingreso de {$cuota->sub_monto} Bs por la cuota #{$cuota->nro_cuota} de la venta #{$cuota->venta_id} en la caja activa (Caja #{$caja->id}).",
        ]);

        return back()->with('success', "Cuota #{$cuota->nro_cuota} cobrada correctamente.");
    }
}
