<?php

namespace App\Http\Controllers;

use App\Models\VentaCuotas;
use App\Services\CajaService;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClienteCuotaController extends Controller
{
    public function pay(Request $request, VentaCuotas $cuota, CajaService $cajaService, StripeService $stripe): JsonResponse
    {
        $user = $request->user();

        $venta = $cuota->venta;
        abort_unless($venta && $venta->cliente_id === $user->id, 403, 'Esta cuota no te pertenece.');
        abort_if($cuota->estado === 'pagado', 400, 'Esta cuota ya fue pagada.');

        $caja = $cajaService->activeCaja($user) ?? $cajaService->activeCajaDelSistema();
        abort_unless($caja, 403, 'No hay ninguna caja abierta en el sistema.');

        $validated = $request->validate([
            'payment_method' => 'required|string|in:qr,tarjeta',
            'card_number' => 'required_if:payment_method,tarjeta|string|nullable',
            'card_expiry' => 'required_if:payment_method,tarjeta|string|nullable',
            'card_cvc' => 'required_if:payment_method,tarjeta|string|nullable',
        ]);

        $paymentMethod = $validated['payment_method'];

        if ($paymentMethod === 'tarjeta') {
            $expiry = explode('/', $validated['card_expiry'] ?? '');
            $expMonth = trim($expiry[0] ?? '');
            $expYear = trim($expiry[1] ?? '');

            if (strlen($expYear) === 2) {
                $expYear = '20'.$expYear;
            }

            $cardDetails = [
                'number' => $validated['card_number'] ?? '',
                'exp_month' => $expMonth,
                'exp_year' => $expYear,
                'cvc' => $validated['card_cvc'] ?? '',
            ];

            $stripeResult = $stripe->chargeWithCard(
                $cuota->sub_monto,
                $cardDetails,
                "Pago Cuota #{$cuota->nro_cuota} - Venta #{$cuota->venta_id}"
            );

            if (! $stripeResult['success']) {
                return response()->json(['error' => $stripeResult['message']], 422);
            }

            $cajaService->registrarPagoCuota($cuota, $user, $caja, 'tarjeta', $stripeResult['charge_id'] ?? null);
        } else {
            $cajaService->registrarPagoCuota($cuota, $user, $caja, $paymentMethod);
        }

        return response()->json(['success' => true, 'message' => "Cuota #{$cuota->nro_cuota} pagada correctamente."]);
    }
}
