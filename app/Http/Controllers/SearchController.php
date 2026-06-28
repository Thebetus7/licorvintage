<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Search products, users, suppliers, or promotions dynamically based on current user modules.
     */
    public function quickSearch(Request $request)
    {
        $query = $request->input('query');
        $user = auth()->user();

        if (empty($query) || !$user) {
            return response()->json([
                'productos' => [],
                'usuarios' => [],
                'proveedores' => [],
                'promociones' => []
            ]);
        }

        $results = [
            'productos' => [],
            'usuarios' => [],
            'proveedores' => [],
            'promociones' => []
        ];

        // Asegurar la extensión unaccent en Postgres si aplica
        $isPgsql = config('database.default') === 'pgsql';
        if ($isPgsql) {
            try {
                \Illuminate\Support\Facades\DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
            } catch (\Exception $e) {
                // Silencioso si no hay permisos de superusuario
            }
        }

        // 1. Productos (si tiene acceso a productos.index o cliente.productos)
        if ($this->hasAccessToModule($user, 'productos.index') || $this->hasAccessToModule($user, 'cliente.productos')) {
            $productos = \App\Models\Producto::with('stockActual')
                ->where(function ($q) use ($query, $isPgsql) {
                    if ($isPgsql) {
                        $q->whereRaw("unaccent(nombre) ILIKE unaccent(?)", ["%{$query}%"])
                            ->orWhereRaw("unaccent(codigo_barra) ILIKE unaccent(?)", ["%{$query}%"])
                            ->orWhereRaw("unaccent(descripcion) ILIKE unaccent(?)", ["%{$query}%"]);
                    } else {
                        $q->where('nombre', 'like', "%{$query}%")
                            ->orWhere('codigo_barra', 'like', "%{$query}%")
                            ->orWhere('descripcion', 'like', "%{$query}%");
                    }
                })
                ->limit(5)
                ->get();

            $results['productos'] = $productos->map(function ($producto) {
                return [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'mililitros' => $producto->mililitros,
                    'precio_venta' => $producto->precio_venta,
                    'descripcion' => $producto->descripcion,
                    'imagen' => $producto->imagen,
                    'codigo_barra' => $producto->codigo_barra,
                    'codigo_qr' => $producto->codigo_qr,
                    'stock' => $producto->stockActual?->stock ?? 0,
                ];
            })->all();
        }

        // 2. Usuarios (si tiene acceso a usuarios.index)
        if ($this->hasAccessToModule($user, 'usuarios.index')) {
            $usuarios = \App\Models\User::where(function ($q) use ($query, $isPgsql) {
                if ($isPgsql) {
                    $q->whereRaw("unaccent(name) ILIKE unaccent(?)", ["%{$query}%"])
                        ->orWhereRaw("unaccent(email) ILIKE unaccent(?)", ["%{$query}%"])
                        ->orWhereRaw("unaccent(ci) ILIKE unaccent(?)", ["%{$query}%"]);
                } else {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%")
                        ->orWhere('ci', 'like', "%{$query}%");
                }
            })
                ->limit(5)
                ->get();

            $results['usuarios'] = $usuarios->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'ci' => $u->ci,
                ];
            })->all();
        }

        // 3. Proveedores (si tiene acceso a proveedores.index)
        if ($this->hasAccessToModule($user, 'proveedores.index')) {
            $proveedores = \App\Models\Proveedor::where(function ($q) use ($query, $isPgsql) {
                if ($isPgsql) {
                    $q->whereRaw("unaccent(nombre) ILIKE unaccent(?)", ["%{$query}%"])
                        ->orWhereRaw("unaccent(telefono) ILIKE unaccent(?)", ["%{$query}%"]);
                } else {
                    $q->where('nombre', 'like', "%{$query}%")
                        ->orWhere('telefono', 'like', "%{$query}%");
                }
            })
                ->limit(5)
                ->get();

            $results['proveedores'] = $proveedores->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'telefono' => $p->telefono,
                    'descripcion' => $p->descripcion,
                ];
            })->all();
        }

        // 4. Promociones (si tiene acceso a promociones.index)
        if ($this->hasAccessToModule($user, 'promociones.index')) {
            $promociones = \App\Models\Promocion::where(function ($q) use ($query, $isPgsql) {
                if ($isPgsql) {
                    $q->whereRaw("unaccent(nombre_promo) ILIKE unaccent(?)", ["%{$query}%"])
                        ->orWhereRaw("unaccent(codigo_promo) ILIKE unaccent(?)", ["%{$query}%"]);
                } else {
                    $q->where('nombre_promo', 'like', "%{$query}%")
                        ->orWhere('codigo_promo', 'like', "%{$query}%");
                }
            })
                ->limit(5)
                ->get();

            $results['promociones'] = $promociones->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nombre_promo' => $p->nombre_promo,
                    'codigo_promo' => $p->codigo_promo,
                    'descuento' => $p->descuento,
                    'tipo_descuento' => $p->tipo_descuento,
                ];
            })->all();
        }

        return response()->json($results);
    }

    /**
     * Check if the authenticated user has access to a specific module.
     */
    private function hasAccessToModule($user, string $routeName): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasRole('propietario')) {
            return true;
        }

        $menuItem = \App\Models\MenuItem::where('route_name', $routeName)->first();
        if (!$menuItem) {
            return false;
        }

        $individualMenus = $user->menus ?: [];
        if (in_array($menuItem->id, $individualMenus) || in_array($menuItem->route_name, $individualMenus)) {
            return true;
        }

        $userRoles = $user->roles->pluck('name')->toArray();
        if ($menuItem->roles && is_array($menuItem->roles)) {
            foreach ($userRoles as $role) {
                if (in_array($role, $menuItem->roles)) {
                    return true;
                }
            }
        }

        return false;
    }
}
