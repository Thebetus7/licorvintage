<?php

namespace App\Http\Controllers;

use App\Services\PagoFacilService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PagoController extends Controller
{
    public function generateQR(Request $request, PagoFacilService $pagofacil): JsonResponse
    {
        $validated = $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'orderDetail' => 'required|array|min:1',
            'orderDetail.*.product' => 'required|string',
            'orderDetail.*.quantity' => 'required|integer|min:1',
            'orderDetail.*.price' => 'required|numeric|min:0',
            'orderDetail.*.discount' => 'nullable|numeric|min:0',
            'orderDetail.*.serial' => 'nullable|integer|min:1',
            'orderDetail.*.total' => 'required|numeric|min:0',
        ]);

        $params = [
            'amount' => $validated['monto'],
            'orderDetail' => $validated['orderDetail'],
        ];

        $qrData = $pagofacil->generateQR($params);

        if (! $qrData) {
            return response()->json([
                'error' => true,
                'message' => 'No se pudo generar el código QR. Intente nuevamente.',
            ], 500);
        }

        return response()->json([
            'error' => false,
            'data' => $qrData,
        ]);
    }

    public function checkStatus(Request $request, PagoFacilService $pagofacil): JsonResponse
    {
        $validated = $request->validate([
            'transactionId' => 'required',
        ]);

        $result = $pagofacil->queryTransaction($validated['transactionId']);

        if (! $result) {
            return response()->json([
                'error' => true,
                'message' => 'No se pudo consultar el estado de la transaccion.',
            ], 500);
        }

        return response()->json([
            'error' => false,
            'data' => $result,
        ]);
    }

    public function callback(Request $request)
    {
        Log::info('Callback PagoFacil', $request->all());

        return response()->json([
            'error' => 0,
            'status' => 1,
            'message' => 'Pago procesado correctamente',
            'values' => true,
        ], 200);
    }
}
