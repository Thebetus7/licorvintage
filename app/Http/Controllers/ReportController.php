<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AjusteInventario;
use App\Models\AperturaCaja;
use App\Models\Compra;
use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function export(Request $request, string $module)
    {
        // 1. Obtener la configuración del módulo específico
        $config = $this->getModuleConfig($module, $request);
        if (! $config) {
            abort(404, 'Módulo de reporte no encontrado.');
        }

        $title = $config['title'];
        $columns = $config['columns'];
        $query = $config['query'];
        $mapCallback = $config['map'];
        $totalsCallback = $config['totals'] ?? null;
        $appliedFilters = $config['applied_filters'] ?? [];

        // 2. Obtener los datos
        $data = $query->get();

        // 3. Procesar los datos (mapear filas)
        $rows = [];
        $accumulatedStock = 0; // Para el Kardex
        foreach ($data as $item) {
            if ($module === 'inventario_kardex') {
                $rows[] = $mapCallback($item, $accumulatedStock);
            } else {
                $rows[] = $mapCallback($item);
            }
        }

        // 4. Calcular los totales
        $totals = [];
        if ($totalsCallback) {
            $totals = $totalsCallback($data, $rows);
        }

        // 5. Determinar el formato de exportación
        $format = strtolower($request->input('format', 'html'));
        $filename = 'reporte_'.$module.'_'.now()->format('Ymd_His');

        if ($format === 'excel') {
            return $this->exportExcel($filename, $columns, $rows);
        }

        $viewData = [
            'title' => $title,
            'columns' => $columns,
            'rows' => $rows,
            'totals' => $totals,
            'applied_filters' => $appliedFilters,
        ];

        if ($format === 'pdf') {
            // Verificar de manera defensiva si DomPDF está instalado
            if (class_exists(Pdf::class)) {
                $pdf = Pdf::loadView('reports.template', $viewData);

                return $pdf->download($filename.'.pdf');
            }

            // Fallback a HTML imprimible si no está instalado DomPDF
            return view('reports.template', $viewData);
        }

        // Formato HTML por defecto
        return view('reports.template', $viewData);
    }

    private function exportExcel(string $filename, array $columns, array $rows)
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($columns, $rows) {
            $file = fopen('php://output', 'w');

            // Añadir el BOM de UTF-8 para compatibilidad nativa con Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Escribir cabeceras
            fputcsv($file, array_values($columns));

            // Escribir filas
            foreach ($rows as $row) {
                $line = [];
                foreach (array_keys($columns) as $key) {
                    $line[] = $row[$key] ?? '';
                }
                fputcsv($file, $line);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getModuleConfig(string $module, Request $request): ?array
    {
        $appliedFilters = [];

        switch ($module) {
            case 'productos':
                $query = Producto::query()->with('stockActual');

                if ($request->filled('precio_min')) {
                    $query->where('precio_venta', '>=', $request->input('precio_min'));
                    $appliedFilters['Precio Mínimo'] = $request->input('precio_min').' Bs';
                }
                if ($request->filled('precio_max')) {
                    $query->where('precio_venta', '<=', $request->input('precio_max'));
                    $appliedFilters['Precio Máximo'] = $request->input('precio_max').' Bs';
                }

                $stockFilter = $request->input('stock_filter');
                if ($stockFilter === 'low') {
                    $query->whereHas('stockActual', fn ($q) => $q->whereColumn('stock', '<=', 'min'));
                    $appliedFilters['Filtro de Stock'] = 'Bajo el Mínimo';
                } elseif ($stockFilter === 'out') {
                    $query->whereHas('stockActual', fn ($q) => $q->where('stock', 0));
                    $appliedFilters['Filtro de Stock'] = 'Agotado';
                }

                return [
                    'title' => 'Reporte de Productos',
                    'query' => $query->orderBy('nombre'),
                    'columns' => [
                        'id' => 'ID',
                        'nombre' => 'Nombre',
                        'codigo_barra' => 'Código de Barra',
                        'costo' => 'Costo (Bs)',
                        'precio_venta' => 'Precio Venta (Bs)',
                        'stock' => 'Stock Actual',
                    ],
                    'map' => fn ($item) => [
                        'id' => $item->id,
                        'nombre' => $item->nombre,
                        'codigo_barra' => $item->codigo_barra ?? 'N/A',
                        'costo' => number_format($item->costo, 2).' Bs',
                        'precio_venta' => number_format($item->precio_venta, 2).' Bs',
                        'stock' => $item->stockActual?->stock ?? 0,
                    ],
                    'totals' => fn ($data, $rows) => [
                        'Total de Productos' => count($rows),
                        'Valorización Total (Costo Promedio)' => number_format($data->sum(fn ($p) => ($p->stockActual?->stock ?? 0) * (float) $p->costo_promedio), 2).' Bs',
                    ],
                    'applied_filters' => $appliedFilters,
                ];

            case 'compras':
                $query = Compra::query()->with(['user', 'proveedor']);

                if ($request->filled('fecha_inicio')) {
                    $query->whereDate('created_at', '>=', $request->input('fecha_inicio'));
                    $appliedFilters['Desde'] = date('d/m/Y', strtotime($request->input('fecha_inicio')));
                }
                if ($request->filled('fecha_fin')) {
                    $query->whereDate('created_at', '<=', $request->input('fecha_fin'));
                    $appliedFilters['Hasta'] = date('d/m/Y', strtotime($request->input('fecha_fin')));
                }
                if ($request->filled('proveedor_id')) {
                    $query->where('proveedor_id', $request->input('proveedor_id'));
                    $prov = Proveedor::find($request->input('proveedor_id'));
                    $appliedFilters['Proveedor'] = $prov ? $prov->nombre : 'ID: '.$request->input('proveedor_id');
                }

                return [
                    'title' => 'Reporte de Compras',
                    'query' => $query->latest(),
                    'columns' => [
                        'id' => 'ID Compra',
                        'fecha' => 'Fecha',
                        'proveedor' => 'Proveedor',
                        'usuario' => 'Registrado Por',
                        'costo' => 'Costo Total',
                    ],
                    'map' => fn ($item) => [
                        'id' => $item->id,
                        'fecha' => $item->created_at->format('d/m/Y H:i'),
                        'proveedor' => $item->proveedor?->nombre ?? 'Sin Proveedor',
                        'usuario' => $item->user?->name ?? 'N/A',
                        'costo' => number_format($item->costo, 2).' Bs',
                    ],
                    'totals' => fn ($data, $rows) => [
                        'Total Transacciones' => count($rows),
                        'Inversión Total Acumulada' => number_format($data->sum('costo'), 2).' Bs',
                    ],
                    'applied_filters' => $appliedFilters,
                ];

            case 'cajas':
                $query = AperturaCaja::query()->with('user');

                if ($request->filled('fecha_inicio')) {
                    $query->whereDate('tiempo_apertura', '>=', $request->input('fecha_inicio'));
                    $appliedFilters['Desde'] = date('d/m/Y', strtotime($request->input('fecha_inicio')));
                }
                if ($request->filled('fecha_fin')) {
                    $query->whereDate('tiempo_apertura', '<=', $request->input('fecha_fin'));
                    $appliedFilters['Hasta'] = date('d/m/Y', strtotime($request->input('fecha_fin')));
                }
                if ($request->filled('estado') && $request->input('estado') !== 'all') {
                    $query->where('estado', $request->input('estado'));
                    $appliedFilters['Estado de Caja'] = ucfirst($request->input('estado'));
                }

                return [
                    'title' => 'Reporte de Cajas (Arqueos e Historial)',
                    'query' => $query->latest('tiempo_apertura'),
                    'columns' => [
                        'id' => 'ID Caja',
                        'apertura' => 'Apertura',
                        'cierre' => 'Cierre',
                        'usuario' => 'Cajero',
                        'monto_inicial' => 'Monto Inicial',
                        'monto_sistema' => 'Monto Sistema',
                        'monto_real' => 'Monto Real',
                        'diferencia' => 'Diferencia',
                        'estado' => 'Estado',
                    ],
                    'map' => fn ($item) => [
                        'id' => $item->id,
                        'apertura' => $item->tiempo_apertura ? $item->tiempo_apertura->format('d/m/Y H:i') : 'N/A',
                        'cierre' => $item->tiempo_cierre ? $item->tiempo_cierre->format('d/m/Y H:i') : 'Abierta',
                        'usuario' => $item->user?->name ?? 'N/A',
                        'monto_inicial' => number_format($item->monto_inicial, 2).' Bs',
                        'monto_sistema' => number_format($item->monto_sistema, 2).' Bs',
                        'monto_real' => number_format($item->monto_real, 2).' Bs',
                        'diferencia' => number_format($item->diferencia, 2).' Bs',
                        'estado' => ucfirst($item->estado),
                    ],
                    'totals' => fn ($data, $rows) => [
                        'Total Cajas Auditadas' => count($rows),
                        'Diferencia Neta Acumulada' => number_format($data->sum('diferencia'), 2).' Bs',
                    ],
                    'applied_filters' => $appliedFilters,
                ];

            case 'creditos':
                $query = Venta::query()->where('tipo_pago', 'credito')->whereColumn('monto_pagado', '<', 'monto_final')->with('cliente');

                if ($request->filled('fecha_inicio')) {
                    $query->whereDate('created_at', '>=', $request->input('fecha_inicio'));
                    $appliedFilters['Desde'] = date('d/m/Y', strtotime($request->input('fecha_inicio')));
                }
                if ($request->filled('fecha_fin')) {
                    $query->whereDate('created_at', '<=', $request->input('fecha_fin'));
                    $appliedFilters['Hasta'] = date('d/m/Y', strtotime($request->input('fecha_fin')));
                }
                if ($request->filled('cliente_id')) {
                    $query->where('cliente_id', $request->input('cliente_id'));
                    $cli = User::find($request->input('cliente_id'));
                    $appliedFilters['Cliente'] = $cli ? $cli->name : 'ID: '.$request->input('cliente_id');
                }

                return [
                    'title' => 'Reporte de Créditos Vigentes',
                    'query' => $query->latest(),
                    'columns' => [
                        'id' => 'ID Venta',
                        'fecha' => 'Fecha Venta',
                        'cliente' => 'Cliente / Deudor',
                        'monto_final' => 'Monto Total',
                        'monto_pagado' => 'Monto Pagado',
                        'saldo_pendiente' => 'Saldo Pendiente',
                        'cuotas' => 'Nro Cuotas',
                    ],
                    'map' => function ($item) {
                        $saldo = $item->monto_final - $item->monto_pagado;

                        return [
                            'id' => $item->id,
                            'fecha' => $item->created_at->format('d/m/Y H:i'),
                            'cliente' => $item->cliente?->name ?? 'Cliente General',
                            'monto_final' => number_format($item->monto_final, 2).' Bs',
                            'monto_pagado' => number_format($item->monto_pagado, 2).' Bs',
                            'saldo_pendiente' => number_format($saldo, 2).' Bs',
                            'cuotas' => $item->nro_cuotas ?? 'N/A',
                        ];
                    },
                    'totals' => fn ($data, $rows) => [
                        'Créditos Vigentes' => count($rows),
                        'Total Deuda por Cobrar' => number_format($data->sum(fn ($v) => $v->monto_final - $v->monto_pagado), 2).' Bs',
                    ],
                    'applied_filters' => $appliedFilters,
                ];

            case 'promociones':
                $query = Promocion::query();

                if ($request->filled('fecha_inicio')) {
                    $query->whereDate('fecha_inicio', '>=', $request->input('fecha_inicio'));
                    $appliedFilters['Desde'] = date('d/m/Y', strtotime($request->input('fecha_inicio')));
                }
                if ($request->filled('fecha_fin')) {
                    $query->whereDate('fecha_fin', '<=', $request->input('fecha_fin'));
                    $appliedFilters['Hasta'] = date('d/m/Y', strtotime($request->input('fecha_fin')));
                }
                if ($request->filled('activa') && $request->input('activa') !== 'all') {
                    if ($request->input('activa') === 'si') {
                        $query->whereDate('fecha_inicio', '<=', now())->whereDate('fecha_fin', '>=', now());
                        $appliedFilters['Estado'] = 'Vigentes (Activas)';
                    } else {
                        $query->where(fn ($q) => $q->whereDate('fecha_inicio', '>', now())->orWhereDate('fecha_fin', '<', now()));
                        $appliedFilters['Estado'] = 'Inactivas o Expiradas';
                    }
                }

                return [
                    'title' => 'Reporte de Promociones y Ofertas',
                    'query' => $query->latest(),
                    'columns' => [
                        'id' => 'ID',
                        'nombre' => 'Promoción',
                        'codigo' => 'Código',
                        'descuento' => 'Descuento',
                        'tipo' => 'Tipo',
                        'vigencia' => 'Vigencia',
                    ],
                    'map' => function ($item) {
                        $tipo = $item->tipo_descuento === 'porcentaje' ? 'Porcentaje (%)' : 'Monto Fijo (Bs)';
                        $desc = $item->tipo_descuento === 'porcentaje' ? $item->descuento.'%' : number_format($item->descuento, 2).' Bs';

                        return [
                            'id' => $item->id,
                            'nombre' => $item->nombre_promo,
                            'codigo' => $item->codigo_promo ?? 'Sin Código',
                            'descuento' => $desc,
                            'tipo' => $tipo,
                            'vigencia' => $item->fecha_inicio->format('d/m/Y').' al '.$item->fecha_fin->format('d/m/Y'),
                        ];
                    },
                    'totals' => fn ($data, $rows) => [
                        'Total Promociones' => count($rows),
                    ],
                    'applied_filters' => $appliedFilters,
                ];

            case 'inventario_movimientos':
                $query = MovimientoInventario::query()->with(['producto', 'user']);

                if ($request->filled('producto_id')) {
                    $query->where('producto_id', $request->input('producto_id'));
                    $prod = Producto::find($request->input('producto_id'));
                    $appliedFilters['Producto'] = $prod ? $prod->nombre : 'ID: '.$request->input('producto_id');
                }
                if ($request->filled('tipo')) {
                    $query->where('tipo', $request->input('tipo'));
                    $appliedFilters['Tipo Movimiento'] = str_replace('_', ' ', ucfirst($request->input('tipo')));
                }
                if ($request->filled('fecha_inicio')) {
                    $query->whereDate('created_at', '>=', $request->input('fecha_inicio'));
                    $appliedFilters['Desde'] = date('d/m/Y', strtotime($request->input('fecha_inicio')));
                }
                if ($request->filled('fecha_fin')) {
                    $query->whereDate('created_at', '<=', $request->input('fecha_fin'));
                    $appliedFilters['Hasta'] = date('d/m/Y', strtotime($request->input('fecha_fin')));
                }

                return [
                    'title' => 'Reporte de Movimientos de Inventario',
                    'query' => $query->latest(),
                    'columns' => [
                        'id' => 'ID Movimiento',
                        'fecha' => 'Fecha',
                        'producto' => 'Producto',
                        'tipo' => 'Tipo',
                        'cantidad' => 'Cantidad',
                        'usuario' => 'Registrado Por',
                    ],
                    'map' => fn ($item) => [
                        'id' => $item->id,
                        'fecha' => $item->created_at->format('d/m/Y H:i'),
                        'producto' => $item->producto?->nombre ?? 'N/A',
                        'tipo' => str_replace('_', ' ', ucfirst($item->tipo)),
                        'cantidad' => $item->cantidad,
                        'usuario' => $item->user?->name ?? 'N/A',
                    ],
                    'totals' => fn ($data, $rows) => [
                        'Total Movimientos' => count($rows),
                        'Cantidad Neta Registrada' => $data->sum(fn ($m) => str_starts_with($m->tipo, 'ingreso') ? $m->cantidad : -$m->cantidad).' unidades',
                    ],
                    'applied_filters' => $appliedFilters,
                ];

            case 'inventario_kardex':
                $productoId = $request->integer('producto_id');
                if (! $productoId) {
                    abort(400, 'Debe seleccionar un producto para generar el reporte Kardex.');
                }

                $query = MovimientoInventario::query()
                    ->with('user')
                    ->where('producto_id', $productoId)
                    ->orderBy('created_at')
                    ->orderBy('id');

                $prod = Producto::find($productoId);
                $appliedFilters['Producto (Obligatorio)'] = $prod ? $prod->nombre : 'ID: '.$productoId;

                if ($request->filled('fecha_inicio')) {
                    $query->whereDate('created_at', '>=', $request->input('fecha_inicio'));
                    $appliedFilters['Desde'] = date('d/m/Y', strtotime($request->input('fecha_inicio')));
                }
                if ($request->filled('fecha_fin')) {
                    $query->whereDate('created_at', '<=', $request->input('fecha_fin'));
                    $appliedFilters['Hasta'] = date('d/m/Y', strtotime($request->input('fecha_fin')));
                }

                return [
                    'title' => 'Kardex de Inventario - '.($prod ? $prod->nombre : 'Producto'),
                    'query' => $query,
                    'columns' => [
                        'id' => 'ID Mov.',
                        'fecha' => 'Fecha',
                        'detalle' => 'Detalle / Motivo',
                        'tipo' => 'Tipo',
                        'cantidad' => 'Cantidad',
                        'saldo' => 'Saldo Stock',
                    ],
                    'map' => function ($item, &$accumulatedStock) {
                        if (str_starts_with($item->tipo, 'ingreso')) {
                            $accumulatedStock += $item->cantidad;
                            $tipoStr = 'Entrada (+)';
                        } else {
                            $accumulatedStock -= $item->cantidad;
                            $tipoStr = 'Salida (-)';
                        }

                        return [
                            'id' => $item->id,
                            'fecha' => $item->created_at->format('d/m/Y H:i'),
                            'detalle' => $item->detalle ?? 'Ajuste de Inventario',
                            'tipo' => $tipoStr,
                            'cantidad' => $item->cantidad,
                            'saldo' => $accumulatedStock,
                        ];
                    },
                    'totals' => function ($data, $rows) {
                        $lastRow = end($rows);

                        return [
                            'Total de Movimientos' => count($rows),
                            'Stock Final Acumulado en el Periodo' => $lastRow ? $lastRow['saldo'] : 0,
                        ];
                    },
                    'applied_filters' => $appliedFilters,
                ];

            case 'inventario_lotes':
                $query = Lote::query()->with('producto');

                if ($request->filled('producto_id')) {
                    $query->where('producto_id', $request->input('producto_id'));
                    $prod = Producto::find($request->input('producto_id'));
                    $appliedFilters['Producto'] = $prod ? $prod->nombre : 'ID: '.$request->input('producto_id');
                }
                if ($request->filled('estado') && $request->input('estado') !== 'all') {
                    $query->where('estado', $request->input('estado'));
                    $appliedFilters['Estado'] = ucfirst($request->input('estado'));
                }

                return [
                    'title' => 'Reporte de Lotes y Fechas de Expiración',
                    'query' => $query->orderByRaw('fecha_expiracion ASC NULLS LAST'),
                    'columns' => [
                        'id' => 'ID Lote',
                        'producto' => 'Producto',
                        'codigo_lote' => 'Código Lote',
                        'cantidad_inicial' => 'Cant. Inicial',
                        'cantidad_actual' => 'Cant. Actual',
                        'expiracion' => 'Fecha Expiración',
                        'estado' => 'Estado',
                    ],
                    'map' => fn ($item) => [
                        'id' => $item->id,
                        'producto' => $item->producto?->nombre ?? 'N/A',
                        'codigo_lote' => $item->codigo_lote ?? 'N/A',
                        'cantidad_inicial' => $item->cantidad_inicial,
                        'cantidad_actual' => $item->cantidad_actual,
                        'expiracion' => $item->fecha_expiracion ? $item->fecha_expiracion->format('d/m/Y') : 'N/A',
                        'estado' => ucfirst($item->estado),
                    ],
                    'totals' => fn ($data, $rows) => [
                        'Lotes Registrados' => count($rows),
                        'Cantidad Actual Disponible' => $data->sum('cantidad_actual').' unidades',
                    ],
                    'applied_filters' => $appliedFilters,
                ];

            case 'inventario_conteos':
                $query = AjusteInventario::query()->with(['producto', 'user']);

                if ($request->filled('producto_id')) {
                    $query->where('producto_id', $request->input('producto_id'));
                    $prod = Producto::find($request->input('producto_id'));
                    $appliedFilters['Producto'] = $prod ? $prod->nombre : 'ID: '.$request->input('producto_id');
                }
                if ($request->filled('fecha_inicio')) {
                    $query->whereDate('created_at', '>=', $request->input('fecha_inicio'));
                    $appliedFilters['Desde'] = date('d/m/Y', strtotime($request->input('fecha_inicio')));
                }
                if ($request->filled('fecha_fin')) {
                    $query->whereDate('created_at', '<=', $request->input('fecha_fin'));
                    $appliedFilters['Hasta'] = date('d/m/Y', strtotime($request->input('fecha_fin')));
                }

                return [
                    'title' => 'Reporte de Conteos Físicos y Ajustes',
                    'query' => $query->latest(),
                    'columns' => [
                        'id' => 'ID Ajuste',
                        'fecha' => 'Fecha',
                        'producto' => 'Producto',
                        'stock_sistema' => 'Stock Sistema',
                        'stock_fisico' => 'Stock Físico',
                        'diferencia' => 'Diferencia',
                        'motivo' => 'Motivo',
                        'usuario' => 'Ajustado Por',
                    ],
                    'map' => function ($item) {
                        $dif = $item->diferencia;

                        return [
                            'id' => $item->id,
                            'fecha' => $item->created_at->format('d/m/Y H:i'),
                            'producto' => $item->producto?->nombre ?? 'N/A',
                            'stock_sistema' => $item->stock_sistema,
                            'stock_fisico' => $item->stock_fisico,
                            'diferencia' => $dif > 0 ? '+'.$dif : $dif,
                            'motivo' => $item->motivo ?? 'N/A',
                            'usuario' => $item->user?->name ?? 'N/A',
                        ];
                    },
                    'totals' => fn ($data, $rows) => [
                        'Conteos Registrados' => count($rows),
                        'Diferencia Total Neta' => $data->sum('diferencia').' unidades',
                    ],
                    'applied_filters' => $appliedFilters,
                ];

            case 'usuarios':
                $query = User::query()->with('roles');

                if ($request->filled('role') && $request->input('role') !== 'all') {
                    $query->whereHas('roles', fn ($q) => $q->where('name', $request->input('role')));
                    $appliedFilters['Rol de Usuario'] = ucfirst($request->input('role'));
                }

                return [
                    'title' => 'Reporte de Usuarios Registrados',
                    'query' => $query->orderBy('name'),
                    'columns' => [
                        'id' => 'ID',
                        'nombre' => 'Nombre completo',
                        'email' => 'Correo Electrónico',
                        'rol' => 'Rol Asignado',
                        'fecha' => 'Fecha Registro',
                    ],
                    'map' => fn ($item) => [
                        'id' => $item->id,
                        'nombre' => $item->name,
                        'email' => $item->email,
                        'rol' => $item->roles->pluck('name')->implode(', ') ?: 'Sin Rol',
                        'fecha' => $item->created_at->format('d/m/Y H:i'),
                    ],
                    'totals' => fn ($data, $rows) => [
                        'Total de Usuarios' => count($rows),
                    ],
                    'applied_filters' => $appliedFilters,
                ];

            case 'seguridad_bitacora':
                $query = ActivityLog::query()->with('user');

                if ($request->filled('user_id')) {
                    $query->where('user_id', $request->input('user_id'));
                    $usr = User::find($request->input('user_id'));
                    $appliedFilters['Usuario'] = $usr ? $usr->name : 'ID: '.$request->input('user_id');
                }
                if ($request->filled('event_type') && $request->input('event_type') !== 'all') {
                    $query->where('event_type', $request->input('event_type'));
                    $appliedFilters['Tipo de Evento'] = str_replace('_', ' ', ucfirst($request->input('event_type')));
                }
                if ($request->filled('fecha_inicio')) {
                    $query->whereDate('created_at', '>=', $request->input('fecha_inicio'));
                    $appliedFilters['Desde'] = date('d/m/Y', strtotime($request->input('fecha_inicio')));
                }
                if ($request->filled('fecha_fin')) {
                    $query->whereDate('created_at', '<=', $request->input('fecha_fin'));
                    $appliedFilters['Hasta'] = date('d/m/Y', strtotime($request->input('fecha_fin')));
                }

                return [
                    'title' => 'Reporte de Bitácora de Auditoría',
                    'query' => $query->latest(),
                    'columns' => [
                        'id' => 'ID Log',
                        'fecha' => 'Fecha y Hora',
                        'usuario' => 'Usuario',
                        'tipo' => 'Tipo Evento',
                        'recurso' => 'Recurso',
                        'descripcion' => 'Descripción',
                        'ip' => 'IP',
                    ],
                    'map' => fn ($item) => [
                        'id' => $item->id,
                        'fecha' => $item->created_at->format('d/m/Y H:i:s'),
                        'usuario' => $item->user?->name ?? 'Invitado / Sistema',
                        'tipo' => str_replace('_', ' ', ucfirst($item->event_type)),
                        'recurso' => $item->resource_name ?? 'N/A',
                        'descripcion' => $item->description ?? 'Sin descripción',
                        'ip' => $item->ip_address ?? 'N/A',
                    ],
                    'totals' => fn ($data, $rows) => [
                        'Total de Logs Auditados' => count($rows),
                    ],
                    'applied_filters' => $appliedFilters,
                ];

            case 'seguridad_recursos':
                $query = ActivityLog::where('event_type', 'resource_access')
                    ->select('resource_name', DB::raw('count(*) as total'))
                    ->groupBy('resource_name')
                    ->orderByDesc('total');

                if ($request->filled('fecha_inicio')) {
                    $query->whereDate('created_at', '>=', $request->input('fecha_inicio'));
                    $appliedFilters['Desde'] = date('d/m/Y', strtotime($request->input('fecha_inicio')));
                }
                if ($request->filled('fecha_fin')) {
                    $query->whereDate('created_at', '<=', $request->input('fecha_fin'));
                    $appliedFilters['Hasta'] = date('d/m/Y', strtotime($request->input('fecha_fin')));
                }

                return [
                    'title' => 'Reporte de Estadísticas de Recursos más Accedidos',
                    'query' => $query,
                    'columns' => [
                        'recurso' => 'Nombre del Recurso',
                        'total' => 'Total Accesos en Bitácora',
                    ],
                    'map' => fn ($item) => [
                        'recurso' => $item->resource_name ?? 'N/A',
                        'total' => $item->total,
                    ],
                    'totals' => fn ($data, $rows) => [
                        'Total Recursos Monitoreados' => count($rows),
                        'Accesos Totales Registrados' => $data->sum('total'),
                    ],
                    'applied_filters' => $appliedFilters,
                ];
        }

        return null;
    }
}
