<?php

namespace App\Http\Middleware;

use App\Models\MenuItem;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeMenuAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $resource = null): Response
    {
        $user = auth()->user();

        if (! $user) {
            abort(401);
        }

        // Propietario tiene acceso irrestricto en todo el sistema
        if ($user->hasRole('propietario')) {
            return $next($request);
        }

        // Si no se especifica el recurso, lo inferimos de la ruta actual
        $routeName = $resource ?: ($request->route() ? $request->route()->getName() : null);

        if (! $routeName) {
            abort(403, 'Acceso denegado: Ruta no identificada.');
        }

        // Encontrar el MenuItem correspondiente por su prefijo de recurso (ej: productos.index -> productos)
        $menuItem = MenuItem::all()->first(function ($item) use ($routeName) {
            $itemRouteParts = explode('.', $item->route_name);
            $currentRouteParts = explode('.', $routeName);

            $itemRouteRoot = $itemRouteParts[0] ?? null;
            $currentRouteRoot = $currentRouteParts[0] ?? null;

            return $itemRouteRoot && $itemRouteRoot === $currentRouteRoot;
        });

        if (! $menuItem) {
            // Si la ruta no es un ítem de menú controlado por la matriz, se permite el paso
            return $next($request);
        }

        // 1. Validar acceso individual (columna menus del usuario)
        $individualMenus = $user->menus ?: [];
        $hasIndividualAccess = false;

        if (in_array($menuItem->id, $individualMenus) || in_array($menuItem->route_name, $individualMenus)) {
            $hasIndividualAccess = true;
        }

        // 2. Validar acceso general por Rol (matriz de roles del MenuItem)
        $hasRoleAccess = false;
        $userRoles = $user->roles->pluck('name')->toArray();
        if ($menuItem->roles && is_array($menuItem->roles)) {
            foreach ($userRoles as $role) {
                if (in_array($role, $menuItem->roles)) {
                    $hasRoleAccess = true;
                    break;
                }
            }
        }

        // Si tiene permiso individual o su rol lo tiene globalmente, se concede el acceso
        if ($hasIndividualAccess || $hasRoleAccess) {
            return $next($request);
        }

        abort(403, 'No tienes permisos para acceder a esta sección.');
    }
}
