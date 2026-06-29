<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Compra;
use App\Models\DetalleVenta;
use App\Models\PageView;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Si es cliente, redirigir al catálogo de productos del cliente
        if ($user->hasRole('cliente')) {
            return redirect()->route('cliente.productos');
        }

        $isPropietario = $user->hasRole('propietario');

        // Filtros del Período
        $year = $request->integer('year') ?: now()->year;
        $monthInput = $request->input('month');
        // Por defecto, si no se envía mes, seleccionamos el mes actual
        $month = $monthInput === 'all' ? 'all' : ($monthInput ? (int) $monthInput : now()->month);
        $vendedorId = $isPropietario ? ($request->integer('vendedor_id') ?: null) : $user->id;

        // --- CONSULTA BASE DE VENTAS DEL PERÍODO ---
        $ventasQuery = Venta::query();

        if ($isPropietario) {
            if ($vendedorId) {
                $ventasQuery->where('user_id', $vendedorId);
            }
        } else {
            $ventasQuery->where('user_id', $user->id);
        }

        if ($year) {
            $ventasQuery->whereYear('created_at', $year);
        }
        if ($month && $month !== 'all') {
            $ventasQuery->whereMonth('created_at', $month);
        }

        $ventas = $ventasQuery->select('id', 'monto_final', 'created_at', 'user_id')->get();

        // --- CÁLCULO DE KPIS ---
        $ventasTotales = (float) $ventas->sum('monto_final');
        $transacciones = $ventas->count();
        $ticketPromedio = $transacciones > 0 ? $ventasTotales / $transacciones : 0.0;

        // Compras Totales (Solo para Propietario)
        $comprasTotales = 0.0;
        if ($isPropietario) {
            $comprasQuery = Compra::query();
            if ($year) {
                $comprasQuery->whereYear('created_at', $year);
            }
            if ($month && $month !== 'all') {
                $comprasQuery->whereMonth('created_at', $month);
            }
            $comprasTotales = (float) $comprasQuery->sum('costo');
        }

        // --- CÁLCULO DE GRÁFICOS ---

        // 1. Tendencia de Ventas (Línea / Área)
        $chartLabels = [];
        $chartValues = [];

        if ($month === 'all') {
            // Mostrar los 12 meses
            $salesData = array_fill(1, 12, 0.0);
            foreach ($ventas as $venta) {
                $m = $venta->created_at->month;
                $salesData[$m] += (float) $venta->monto_final;
            }
            $chartLabels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            $chartValues = array_values($salesData);
        } else {
            // Mostrar los días del mes seleccionado
            $daysInMonth = Carbon::create($year, $month)->daysInMonth;
            $salesData = array_fill(1, $daysInMonth, 0.0);
            foreach ($ventas as $venta) {
                $day = $venta->created_at->day;
                $salesData[$day] += (float) $venta->monto_final;
            }
            $chartLabels = array_map(fn ($d) => 'Día '.$d, array_keys($salesData));
            $chartValues = array_values($salesData);
        }

        // 2. Top 5 Productos Más Vendidos
        $topProductos = DetalleVenta::query()
            ->whereIn('venta_id', $ventas->pluck('id'))
            ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
            ->select('productos.nombre', DB::raw('SUM(detalle_ventas.cantidad) as total_cantidad'))
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('total_cantidad')
            ->limit(5)
            ->get()
            ->map(fn ($item) => [
                'name' => $item->nombre,
                'value' => (int) $item->total_cantidad,
            ]);

        // 3. Ventas por Vendedor (Solo Propietario)
        $ventasPorVendedor = [];
        if ($isPropietario) {
            $ventasPorVendedor = Venta::query()
                ->whereIn('ventas.id', $ventas->pluck('id'))
                ->join('users', 'ventas.user_id', '=', 'users.id')
                ->select('users.name', DB::raw('SUM(ventas.monto_final) as total_ventas'))
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('total_ventas')
                ->get()
                ->map(fn ($item) => [
                    'name' => $item->name,
                    'value' => (float) $item->total_ventas,
                ]);
        }

        // 4. Distribución por Día de la Semana
        $salesByDayOfWeek = [
            1 => ['name' => 'Lunes', 'total' => 0.0],
            2 => ['name' => 'Martes', 'total' => 0.0],
            3 => ['name' => 'Miércoles', 'total' => 0.0],
            4 => ['name' => 'Jueves', 'total' => 0.0],
            5 => ['name' => 'Viernes', 'total' => 0.0],
            6 => ['name' => 'Sábado', 'total' => 0.0],
            7 => ['name' => 'Domingo', 'total' => 0.0],
        ];
        foreach ($ventas as $venta) {
            $dayOfWeek = $venta->created_at->dayOfWeekIso; // 1 (Lunes) a 7 (Domingo)
            if (isset($salesByDayOfWeek[$dayOfWeek])) {
                $salesByDayOfWeek[$dayOfWeek]['total'] += (float) $venta->monto_final;
            }
        }
        $dayOfWeekLabels = array_column($salesByDayOfWeek, 'name');
        $dayOfWeekValues = array_map(fn ($val) => round($val, 2), array_column($salesByDayOfWeek, 'total'));

        // --- DATOS DE TRÁFICO Y AUDITORÍA (Solo Propietario) ---
        $paginasMasVisitadas = [];
        $recursosMasAccedidos = [];
        $vendedores = [];

        if ($isPropietario) {
            $paginasMasVisitadas = PageView::orderByDesc('views_count')->limit(5)->get();

            $recursosMasAccedidos = ActivityLog::where('event_type', 'resource_access')
                ->select('resource_name', DB::raw('count(*) as total'))
                ->groupBy('resource_name')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            $vendedores = User::whereHas('roles', fn ($q) => $q->where('name', 'vendedor'))->get(['id', 'name']);
        }

        if ($request->wantsJson() || $request->has('json')) {
            return response()->json([
                'kpis' => [
                    'ventas_totales' => $ventasTotales,
                    'compras_totales' => $comprasTotales,
                    'transacciones' => $transacciones,
                    'ticket_promedio' => round($ticketPromedio, 2),
                ],
                'chart_tendencia' => [
                    'labels' => $chartLabels,
                    'values' => $chartValues,
                ],
                'chart_productos' => $topProductos,
                'chart_vendedores' => $ventasPorVendedor,
                'chart_dias' => [
                    'labels' => $dayOfWeekLabels,
                    'values' => $dayOfWeekValues,
                ],
            ]);
        }

        return Inertia::render('Dashboard', [
            'is_propietario' => $isPropietario,
            'filters' => [
                'year' => $year,
                'month' => $monthInput === 'all' ? 'all' : $month,
                'vendedor_id' => $vendedorId,
            ],
            'vendedores' => $vendedores,
            'kpis' => [
                'ventas_totales' => $ventasTotales,
                'compras_totales' => $comprasTotales,
                'transacciones' => $transacciones,
                'ticket_promedio' => round($ticketPromedio, 2),
            ],
            'chart_tendencia' => [
                'labels' => $chartLabels,
                'values' => $chartValues,
            ],
            'chart_productos' => $topProductos,
            'chart_vendedores' => $ventasPorVendedor,
            'chart_dias' => [
                'labels' => $dayOfWeekLabels,
                'values' => $dayOfWeekValues,
            ],
            'paginas_visitadas' => $paginasMasVisitadas,
            'recursos_accedidos' => $recursosMasAccedidos,
        ]);
    }
}
