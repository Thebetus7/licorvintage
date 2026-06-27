<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AperturaCaja;
use App\Models\User;
use App\Models\VentaCuotas;
use Illuminate\Validation\ValidationException;

class CajaService
{
    public function open(User $opener, int $vendedorId, float $montoInicial): AperturaCaja
    {
        $vendedor = User::findOrFail($vendedorId);

        $activeCaja = $this->activeCaja($vendedor);
        if ($activeCaja) {
            throw ValidationException::withMessages([
                'vendedor_id' => "{$vendedor->name} ya tiene una caja abierta.",
            ]);
        }

        $totalesIniciales = [
            'efectivo' => 0,
            'qr' => 0,
            'tarjeta' => 0,
            'credito' => 0,
        ];

        $caja = AperturaCaja::create([
            'monto_inicial' => $montoInicial,
            'monto_sistema' => $montoInicial,
            'monto_real' => null,
            'diferencia' => null,
            'tiempo_apertura' => now(),
            'tiempo_cierre' => null,
            'estado' => 'abierta',
            'user_id' => $vendedor->id,
            'opened_by' => $opener->id,
            'totales_sistema' => $totalesIniciales,
            'totales_caja' => null,
        ]);

        ActivityLog::create([
            'event_type' => 'caja_opened',
            'user_id' => $opener->id,
            'user_identity' => $opener->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'resource_name' => 'Caja',
            'visited_url' => request()->getRequestUri(),
            'description' => "Apertura de caja realizada por {$opener->name} para {$vendedor->name} (Caja #{$caja->id}) con monto inicial de {$montoInicial} Bs.",
        ]);

        return $caja;
    }

    public function close(AperturaCaja $caja, array $totalesCaja): AperturaCaja
    {
        $montoReal = array_sum($totalesCaja);
        $diferencia = $montoReal - $caja->monto_sistema;

        $caja->update([
            'totales_caja' => $totalesCaja,
            'monto_real' => $montoReal,
            'diferencia' => $diferencia,
            'tiempo_cierre' => now(),
            'estado' => 'cerrada',
        ]);

        ActivityLog::create([
            'event_type' => 'caja_closed',
            'user_id' => auth()->id(),
            'user_identity' => auth()->user()?->email ?? 'sistema',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'resource_name' => 'Caja',
            'visited_url' => request()->getRequestUri(),
            'description' => "Cierre de caja #{$caja->id}. Efectivo: {$totalesCaja['efectivo']} | QR: {$totalesCaja['qr']} | Tarjeta: {$totalesCaja['tarjeta']} | Crédito: {$totalesCaja['credito']}. Diferencia: {$diferencia} Bs.",
        ]);

        return $caja->fresh();
    }

    public function activeCaja(User $user): ?AperturaCaja
    {
        return AperturaCaja::query()
            ->where('user_id', $user->id)
            ->where('estado', 'abierta')
            ->latest()
            ->first();
    }

    public function activeCajaDelSistema(): ?AperturaCaja
    {
        return AperturaCaja::query()
            ->where('estado', 'abierta')
            ->latest()
            ->first();
    }

    public function cajaAbiertaPorPropietario(User $opener): ?AperturaCaja
    {
        return AperturaCaja::query()
            ->where('opened_by', $opener->id)
            ->where('estado', 'abierta')
            ->latest()
            ->first();
    }

    public function registrarPagoCuota(
        VentaCuotas $cuota,
        User $user,
        AperturaCaja $caja,
        string $paymentMethod,
        ?string $chargeId = null
    ): void {
        $cuota->update([
            'estado' => 'pagado',
            'fecha_pago' => now(),
        ]);

        $mapa = [
            'efectivo' => 'efectivo',
            'qr' => 'qr',
            'tarjeta' => 'tarjeta',
        ];
        $clave = $mapa[$paymentMethod] ?? 'efectivo';

        $detalle = "Cobro Cuota #{$cuota->nro_cuota} de Venta #{$cuota->venta_id} ({$paymentMethod})";
        if ($chargeId) {
            $detalle .= " - Charge: {$chargeId}";
        }

        $caja->movimientoCajas()->create([
            'monto' => $cuota->sub_monto,
            'tipo' => 'ingreso_'.$paymentMethod,
            'detalle' => $detalle,
        ]);

        $caja->increment('monto_sistema', $cuota->sub_monto);

        $totales = $caja->totales_sistema;
        $totales[$clave] = ($totales[$clave] ?? 0) + $cuota->sub_monto;
        $caja->updateQuietly(['totales_sistema' => $totales]);

        ActivityLog::create([
            'event_type' => 'caja_movement',
            'user_id' => $user->id,
            'user_identity' => $user->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'resource_name' => 'Caja',
            'visited_url' => request()->getRequestUri(),
            'description' => "Cobro de cuota realizado: {$cuota->sub_monto} Bs via {$paymentMethod} por la cuota #{$cuota->nro_cuota} de la venta #{$cuota->venta_id} en la caja activa (Caja #{$caja->id}).",
        ]);
    }
}
