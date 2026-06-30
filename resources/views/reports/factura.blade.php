<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Licor Vintage</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Outfit', sans-serif; color: #1c1917; background: #fff; margin: 0; padding: 20px; font-size: 12px; line-height: 1.5; }
        .header { border-bottom: 2px solid #f5f5f4; padding-bottom: 16px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start; }
        .header-left h2 { margin: 0 0 4px 0; font-size: 20px; font-weight: 700; color: #2b1115; }
        .header-left p { margin: 0; font-size: 11px; color: #6c6c6c; }
        .header-right { text-align: right; }
        .header-right .logo { font-size: 18px; font-weight: 700; color: #2b1115; margin-bottom: 4px; }
        .header-right .meta { font-size: 10px; color: #a8a29e; }
        .section { margin-bottom: 16px; }
        .section h3 { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: #a8a29e; margin: 0 0 6px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        thead th { text-align: left; padding: 6px 4px; border-bottom: 1px solid #e7e5e4; font-weight: 600; color: #6c6c6c; font-size: 10px; text-transform: uppercase; }
        thead th.right { text-align: right; }
        thead th.center { text-align: center; }
        tbody td { padding: 6px 4px; border-bottom: 1px solid #f5f5f4; }
        tbody td.right { text-align: right; font-family: 'Courier New', monospace; }
        tbody td.center { text-align: center; }
        .totals { margin-top: 12px; border-top: 2px solid #e7e5e4; padding-top: 8px; }
        .totals .row { display: flex; justify-content: space-between; padding: 2px 0; }
        .totals .row.total { font-size: 14px; font-weight: 700; border-top: 1px solid #e7e5e4; padding-top: 6px; margin-top: 4px; }
        .totals .row .label { color: #6c6c6c; }
        .totals .row .value { font-family: 'Courier New', monospace; }
        .info-grid { display: flex; gap: 20px; margin-bottom: 16px; }
        .info-box { flex: 1; padding: 10px; background: #fafaf9; border-radius: 6px; }
        .info-box .label { font-size: 9px; text-transform: uppercase; font-weight: 600; color: #a8a29e; }
        .info-box .value { font-size: 12px; font-weight: 600; color: #1c1917; margin-top: 2px; }
        .footer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #e7e5e4; font-size: 10px; color: #a8a29e; text-align: center; }
        .badge { display: inline-block; padding: 2px 8px; font-size: 9px; font-weight: 600; border-radius: 10px; text-transform: uppercase; }
        .badge-credito { background: #fef3c7; color: #92400e; }
        .badge-normal { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h2>Licor Vintage</h2>
            <p>UAGRM - Tecnología Web • Santa Cruz, Bolivia</p>
        </div>
        <div class="header-right">
            <div class="logo">FACTURA</div>
            <div class="meta">#{{ $venta->id }}</div>
            <div class="meta">{{ $venta->created_at->format('d/m/Y H:i') }}</div>
            <div style="margin-top:4px">
                <span class="badge {{ $venta->tipo_pago === 'credito' ? 'badge-credito' : 'badge-normal' }}">{{ ucfirst($venta->tipo_pago) }}</span>
            </div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <div class="label">Cliente</div>
            <div class="value">{{ $venta->cliente?->name ?? 'Consumidor Final' }}</div>
            @if($venta->cliente?->ci)
                <div style="font-size:10px;color:#6c6c6c">CI: {{ $venta->cliente->ci }}</div>
            @endif
        </div>
        <div class="info-box">
            <div class="label">Vendedor</div>
            <div class="value">{{ $venta->user?->name ?? 'N/A' }}</div>
        </div>
        <div class="info-box">
            <div class="label">Método de Pago</div>
            <div class="value">
                @forelse ($venta->metodoPagos as $mp)
                    <div>{{ ucfirst($mp->tipo_pago) }}: Bs {{ number_format($mp->monto ?? 0, 2) }}</div>
                @empty
                    <div>{{ ucfirst($venta->tipo_pago) }}</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="section">
        <h3>Productos</h3>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="center">Cant</th>
                    <th class="right">P.U.</th>
                    <th class="right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($venta->detalleVentas as $d)
                <tr>
                    <td>{{ $d->producto?->nombre ?? '—' }}</td>
                    <td class="center">{{ $d->cantidad }}</td>
                    <td class="right">Bs {{ number_format($d->precio_u_final, 2) }}</td>
                    <td class="right">Bs {{ number_format($d->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="totals">
        <div class="row">
            <span class="label">Subtotal</span>
            <span class="value">Bs {{ number_format($venta->monto_original, 2) }}</span>
        </div>
        @if ($venta->cod_descuento)
        <div class="row">
            <span class="label">Descuento ({{ $venta->cod_descuento }})</span>
            <span class="value" style="color:#059669">−Bs {{ number_format($venta->monto_original - $venta->monto_final, 2) }}</span>
        </div>
        @endif
        <div class="row total">
            <span class="label">Total</span>
            <span class="value">Bs {{ number_format($venta->monto_final, 2) }}</span>
        </div>
    </div>

    @if ($venta->tipo_pago === 'credito' && $venta->ventaCuotas->isNotEmpty())
    <div class="section" style="margin-top:16px">
        <h3>Cuotas</h3>
        <table>
            <thead>
                <tr>
                    <th># Cuota</th>
                    <th class="right">Monto</th>
                    <th class="center">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($venta->ventaCuotas as $cu)
                <tr>
                    <td>Cuota #{{ $cu->nro_cuota }}</td>
                    <td class="right">Bs {{ number_format($cu->sub_monto, 2) }}</td>
                    <td class="center">{{ $cu->estado === 'pagado' ? 'Pagado' : 'Pendiente' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        Licor Vintage &mdash; Generado el {{ now()->format('d/m/Y H:i') }}
    </div>

    @if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class))
    <script>window.print();</script>
    @endif
</body>
</html>
