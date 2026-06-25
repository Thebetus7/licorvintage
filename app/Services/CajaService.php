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
            throw ValidationException::withMessages([
                'monto_inicial' => 'Ya tienes una caja abierta.',
            ]);
        }

        return AperturaCaja::create([
            'monto_inicial' => $montoInicial,
            'monto_sistema' => $montoInicial,
            'monto_real' => null,
            'diferencia' => null,
            'tiempo_apertura' => now(),
            'tiempo_cierre' => null,
            'estado' => 'abierta',
            'user_id' => $user->id,
        ]);
    }

    public function close(AperturaCaja $caja, float $montoReal): AperturaCaja
    {
        $caja->update([
            'monto_real' => $montoReal,
            'diferencia' => $montoReal - $caja->monto_sistema,
            'tiempo_cierre' => now(),
            'estado' => 'cerrada',
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
