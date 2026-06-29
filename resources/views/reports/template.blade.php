<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Licor Vintage</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Outfit', sans-serif;
            color: #1c1917;
            background-color: #ffffff;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.5;
        }

        .header {
            border-bottom: 2px solid #f5f5f4;
            padding-bottom: 16px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .header-left h2 {
            margin: 0 0 4px 0;
            font-size: 20px;
            font-weight: 700;
            color: #2b1115;
        }

        .header-left p {
            margin: 0;
            font-size: 11px;
            color: #6c6c6c;
        }

        .header-right {
            text-align: right;
        }

        .header-right .logo {
            font-size: 18px;
            font-weight: 700;
            color: #2b1115;
            margin-bottom: 4px;
        }

        .header-right p {
            margin: 2px 0;
            font-size: 10px;
            color: #6c6c6c;
        }

        .filters-box {
            background-color: #fafaf9;
            border: 1px solid #e7e5e4;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 20px;
            font-size: 11px;
        }

        .filters-box strong {
            color: #2b1115;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 8px;
            margin-top: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            page-break-inside: auto;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th {
            background-color: #fafaf9;
            border-bottom: 2px solid #e7e5e4;
            color: #2b1115;
            font-weight: 600;
            text-align: left;
            padding: 8px 10px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 8px 10px;
            border-bottom: 1px solid #f5f5f4;
            color: #44403c;
        }

        tr:nth-child(even) td {
            background-color: #fafaf9/20;
        }

        .summary-block {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .summary-card {
            border: 1px solid #e7e5e4;
            border-radius: 6px;
            background-color: #fafaf9;
            padding: 12px 18px;
            min-width: 220px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 4px 0;
            font-size: 11px;
        }

        .summary-row.total {
            border-top: 1px solid #e7e5e4;
            padding-top: 6px;
            margin-top: 6px;
            font-size: 13px;
            font-weight: 700;
            color: #d97706;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #f5f5f4;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            color: #a8a29e;
            background-color: #ffffff;
        }

        /* Ocultar elementos no imprimibles al imprimir */
        @media print {
            body {
                padding: 0;
            }
            .no-print-bar {
                display: none;
            }
            .footer {
                position: fixed;
                bottom: 0;
            }
        }
    </style>
</head>
<body>



    <!-- Encabezado del Reporte -->
    <div class="header">
        <div class="header-left">
            <h2>{{ $title }}</h2>
            <p>Generado el {{ now()->format('d/m/Y H:i:s') }} por {{ auth()->user()->name }}</p>
        </div>
        <div class="header-right">
            <div class="logo">Licor Vintage</div>
            <p>UAGRM - Tecnología Web</p>
            <p>Santa Cruz, Bolivia</p>
        </div>
    </div>

    <!-- Caja de Filtros Aplicados -->
    @if(count($applied_filters) > 0)
    <div class="filters-box">
        <strong>Filtros aplicados en este reporte:</strong>
        <div class="filters-grid">
            @foreach($applied_filters as $key => $val)
                <div>• {{ $key }}: <strong>{{ $val }}</strong></div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Tabla de Datos -->
    <table>
        <thead>
            <tr>
                @foreach($columns as $col)
                    <th>{{ $col }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $key => $col)
                        <td>{{ $row[$key] ?? 'N/A' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" style="text-align: center; padding: 20px; color: #a8a29e;">
                        No se encontraron registros que coincidan con los filtros seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Bloque de Resumen (Totales, etc. si aplica) -->
    @if(count($totals) > 0)
    <div class="summary-block">
        <div class="summary-card">
            @foreach($totals as $label => $val)
                <div class="summary-row {{ $loop->last ? 'total' : '' }}">
                    <span>{{ $label }}:</span>
                    <strong>{{ $val }}</strong>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Pie de Página del Reporte -->
    <div class="footer">
        <span>Licor Vintage - Sistema de Gestión Comercial</span>
        <span>Página 1 de 1</span>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 300);
        };
    </script>
</body>
</html>
