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
        .info-grid { display: flex; gap: 20px; margin-bottom: 16px; flex-wrap: wrap; }
        .info-box { flex: 1; min-width: 140px; padding: 10px; background: #fafaf9; border-radius: 6px; }
        .info-box .label { font-size: 9px; text-transform: uppercase; font-weight: 600; color: #a8a29e; }
        .info-box .value { font-size: 12px; font-weight: 600; color: #1c1917; margin-top: 2px; }
        .info-box .value.mono { font-family: 'Courier New', monospace; }
        .diff-pos { color: #059669; }
        .diff-neg { color: #dc2626; }
        .footer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #e7e5e4; font-size: 10px; color: #a8a29e; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h2>Licor Vintage</h2>
            <p>UAGRM - Tecnología Web • Santa Cruz, Bolivia</p>
        </div>
        <div class="header-right">
            <div class="logo">APERTURA DE CAJA</div>
            <div class="meta">#{{ $apertura->id }}</div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <div class="label">Vendedor</div>
            <div class="value">{{ $apertura->user?->name ?? '—' }}</div>
        </div>
        <div class="info-box">
            <div class="label">Abierta por</div>
            <div class="value">{{ $apertura->opener?->name ?? '—' }}</div>
        </div>
        <div class="info-box">
            <div class="label">Estado</div>
            <div class="value">{{ ucfirst($apertura->estado) }}</div>
        </div>
        <div class="info-box">
            <div class="label">Apertura</div>
            <div class="value">{{ $apertura->tiempo_apertura ? $apertura->tiempo_apertura->format('d/m/Y H:i') : '—' }}</div>
        </div>
        <div class="info-box">
            <div class="label">Cierre</div>
            <div class="value">{{ $apertura->tiempo_cierre ? $apertura->tiempo_cierre->format('d/m/Y H:i') : 'Abierta' }}</div>
        </div>
    </div>

    <div class="section">
        <h3>Montos</h3>
        <table>
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th class="right">Monto (Bs)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Monto Inicial</td>
                    <td class="right">{{ number_format($apertura->monto_inicial, 2) }}</td>
                </tr>
                <tr>
                    <td>Total Sistema</td>
                    <td class="right">{{ number_format($apertura->monto_sistema, 2) }}</td>
                </tr>
                <tr>
                    <td>Total Real</td>
                    <td class="right">{{ number_format($apertura->monto_real ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td>Diferencia</td>
                    <td class="right {{ ($apertura->diferencia ?? 0) >= 0 ? 'diff-pos' : 'diff-neg' }}">
                        {{ number_format($apertura->diferencia ?? 0, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    @if ($apertura->totales_sistema || $apertura->totales_caja)
    <div class="section">
        <h3>Arqueo por Tipo de Pago</h3>
        <table>
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th class="right">Sistema</th>
                    <th class="right">Caja</th>
                    <th class="right">Dif.</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $tipos = collect(array_keys((array) $apertura->totales_sistema ?? []))
                        ->merge(array_keys((array) $apertura->totales_caja ?? []))
                        ->unique();
                @endphp
                @foreach ($tipos as $tipo)
                @php
                    $sis = (float) ($apertura->totales_sistema[$tipo] ?? 0);
                    $caja = (float) ($apertura->totales_caja[$tipo] ?? 0);
                    $dif = $caja - $sis;
                @endphp
                <tr>
                    <td class="capitalize">{{ $tipo }}</td>
                    <td class="right">{{ number_format($sis, 2) }}</td>
                    <td class="right">{{ number_format($caja, 2) }}</td>
                    <td class="right {{ $dif >= 0 ? 'diff-pos' : 'diff-neg' }}">{{ $dif >= 0 ? '+' : '' }}{{ number_format($dif, 2) }}</td>
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
