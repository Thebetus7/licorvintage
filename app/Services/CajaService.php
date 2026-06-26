<?php

namespace App\Services;

use App\Models\AperturaCaja;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CajaService
{
    public function open(User $user, float $montoInicial): AperturaCaja
    {
        $activeCaja = $this->activeCaja($user);

        if ($activeCaja) {
            \App\Models\ActivityLog::create([
                'event_type' => 'caja_open_failed',
                'user_id' => $user->id,
                'user_identity' => $user->email,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'resource_name' => 'Caja',
                'visited_url' => request()->getRequestUri(),
                'description' => "Intento fallido de apertura de caja. Motivo: El usuario ya posee una caja abierta activa (Caja #{$activeCaja->id}).",
            ]);

            throw ValidationException::withMessages([
                'monto_inicial' => 'Ya tienes una caja abierta.',
            ]);
        }

        $caja = AperturaCaja::create([
            'monto_inicial' => $montoInicial,
            'monto_sistema' => $montoInicial,
            'monto_real' => null,
            'diferencia' => null,
            'tiempo_apertura' => now(),
            'tiempo_cierre' => null,
            'estado' => 'abierta',
            'user_id' => $user->id,
        ]);

        \App\Models\ActivityLog::create([
            'event_type' => 'caja_opened',
            'user_id' => $user->id,
            'user_identity' => $user->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'resource_name' => 'Caja',
            'visited_url' => request()->getRequestUri(),
            'description' => "Apertura de caja realizada exitosamente (Caja #{$caja->id}) con un monto inicial de {$montoInicial} Bs.",
        ]);

        return $caja;
    }

    public function close(AperturaCaja $caja, float $montoReal): AperturaCaja
    {
        $caja->update([
            'monto_real' => $montoReal,
            'diferencia' => $montoReal - $caja->monto_sistema,
            'tiempo_cierre' => now(),
            'estado' => 'cerrada',
        ]);

        \App\Models\ActivityLog::create([
            'event_type' => 'caja_closed',
            'user_id' => auth()->id(),
            'user_identity' => auth()->user()?->email ?? 'sistema',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'resource_name' => 'Caja',
            'visited_url' => request()->getRequestUri(),
            'description' => "Cierre de caja realizado (Caja #{$caja->id}). Monto sistema: {$caja->monto_sistema} Bs. Monto real: {$montoReal} Bs. Diferencia: " . ($montoReal - $caja->monto_sistema) . " Bs.",
        ]);

        return $caja;
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
}
