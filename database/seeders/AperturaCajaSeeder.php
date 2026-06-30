<?php

namespace Database\Seeders;

use App\Models\AperturaCaja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AperturaCajaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vendedores = User::whereHas('roles', fn ($q) => $q->where('name', 'vendedor'))->get();
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'propietario'))->first();

        if ($vendedores->isEmpty() || !$admin) {
            return;
        }

        $startDate = Carbon::create(2025, 1, 1);
        $endDate = Carbon::create(2026, 6, 29);

        while ($startDate->lte($endDate)) {
            // Asignar un cajero aleatorio para el día
            $cajero = rand(0, 4) === 0 ? $admin : $vendedores->random();
            
            $fechaApertura = $startDate->copy()->setHour(8)->setMinute(0)->setSecond(0);
            $fechaCierre = $startDate->copy()->setHour(22)->setMinute(0)->setSecond(0);

            AperturaCaja::create([
                'monto_inicial' => 500.00,
                'monto_sistema' => 500.00, // Se acumulará en VentaSeeder
                'monto_real' => 500.00,    // Se acumulará en VentaSeeder
                'diferencia' => 0.00,
                'tiempo_apertura' => $fechaApertura,
                'tiempo_cierre' => $fechaCierre,
                'estado' => 'cerrado',
                'user_id' => $cajero->id,
                'opened_by' => $cajero->id,
                'totales_sistema' => [
                    'efectivo' => 0.0,
                    'qr' => 0.0,
                    'tarjeta' => 0.0,
                    'credito' => 0.0,
                ],
                'totales_caja' => [
                    'efectivo' => 0.0,
                    'qr' => 0.0,
                    'tarjeta' => 0.0,
                    'credito' => 0.0,
                ],
                'created_at' => $fechaApertura,
                'updated_at' => $fechaCierre,
            ]);

            $startDate->addDay();
        }
    }
}
