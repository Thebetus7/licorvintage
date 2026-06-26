<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use App\Models\MenuItem;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogResourceAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo registrar si el usuario está autenticado y la respuesta fue exitosa/redirección
        if (auth()->check() && $response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {
            $routeName = $request->route() ? $request->route()->getName() : null;

            if ($routeName) {
                // Buscar si la ruta corresponde a algún MenuItem (coincidencia de raíz, ej: productos.*)
                $menuItem = MenuItem::all()->first(function ($item) use ($routeName) {
                    $itemRouteParts = explode('.', $item->route_name);
                    $currentRouteParts = explode('.', $routeName);

                    $itemRouteRoot = $itemRouteParts[0] ?? null;
                    $currentRouteRoot = $currentRouteParts[0] ?? null;

                    return $itemRouteRoot && $itemRouteRoot === $currentRouteRoot;
                });

                if ($menuItem) {
                    ActivityLog::create([
                        'event_type' => 'resource_access',
                        'user_id' => auth()->id(),
                        'user_identity' => auth()->user()->email,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'resource_name' => $menuItem->label,
                        'visited_url' => $request->getRequestUri(),
                        'description' => "Ingreso a la pantalla de {$menuItem->label}.",
                    ]);
                }
            }
        }

        return $response;
    }
}
