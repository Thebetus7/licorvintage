<?php

namespace App\Http\Controllers;

use App\Models\AperturaCaja;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;

class DescargaController extends Controller
{
    public function ventaPdf(Venta $venta)
    {
        $venta->load(['detalleVentas.producto', 'cliente', 'user', 'metodoPagos', 'ventaCuotas']);

        $data = [
            'title' => 'Factura #' . $venta->id,
            'venta' => $venta,
        ];

        $filename = 'factura_' . $venta->id . '_' . now()->format('Ymd_His');

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('reports.factura', $data);
            return $pdf->download($filename . '.pdf');
        }

        return view('reports.factura', $data);
    }

    public function aperturaPdf(AperturaCaja $apertura)
    {
        $apertura->load(['user', 'opener']);

        $data = [
            'title' => 'Apertura de Caja #' . $apertura->id,
            'apertura' => $apertura,
        ];

        $filename = 'apertura_' . $apertura->id . '_' . now()->format('Ymd_His');

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('reports.apertura', $data);
            return $pdf->download($filename . '.pdf');
        }

        return view('reports.apertura', $data);
    }
}
