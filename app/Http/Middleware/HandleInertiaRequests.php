<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\VisitaPagina;
use App\Models\Bitacora;
use Illuminate\Support\Facades\DB;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()?->load('rol'),
                'role' => $request->user()?->rol?->nombre,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            // Dynamic page visitor counter
            'visitas_actual' => function () use ($request) {
                $routeName = $request->route()?->getName() ?? $request->getPathInfo();
                $ruta = $routeName ?: '/';
                return VisitaPagina::where('ruta', $ruta)->first()?->contador ?? 1;
            },
            // Share audit logs and access matrix only with the Propietario (Admin)
            'bitacora_data' => function () use ($request) {
                $user = $request->user();
                if ($user && $user->hasRole('Propietario')) {
                    return [
                        'recent_logs' => Bitacora::with('user')->latest()->take(50)->get(),
                        'stats' => [
                            'logins_exitosos' => Bitacora::where('tipo', 'Login')->where('estado', 'Exitoso')->count(),
                            'logins_fallidos' => Bitacora::where('tipo', 'Login')->where('estado', 'Fallido')->count(),
                            'top_recursos' => Bitacora::where('tipo', 'Acceso')
                                ->select('descripcion', DB::raw('count(*) as total'))
                                ->groupBy('descripcion')
                                ->orderByDesc('total')
                                ->take(5)
                                ->get(),
                        ],
                        'matrix' => [
                            ['modulo' => 'Productos (CRUD)', 'propietario' => true, 'vendedor' => true, 'cliente' => false],
                            ['modulo' => 'Compras (CRUD)', 'propietario' => true, 'vendedor' => true, 'cliente' => false],
                            ['modulo' => 'Proveedores (CRUD)', 'propietario' => true, 'vendedor' => true, 'cliente' => false],
                            ['modulo' => 'Caja / Registro', 'propietario' => true, 'vendedor' => true, 'cliente' => false],
                            ['modulo' => 'Usuarios / Vendedores', 'propietario' => true, 'vendedor' => false, 'cliente' => false],
                            ['modulo' => 'Inventario (Kardex/Ajustes)', 'propietario' => true, 'vendedor' => false, 'cliente' => false],
                            ['modulo' => 'Catálogo Público E-commerce', 'propietario' => true, 'vendedor' => true, 'cliente' => true],
                        ]
                    ];
                }
                return null;
            }
        ];
    }
}
