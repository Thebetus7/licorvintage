<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\VisitaPagina;

class ContadorVisitasMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only count successful GET requests that are standard HTML page requests (non-AJAX)
        if ($request->isMethod('GET') && !$request->ajax() && $response->getStatusCode() === 200) {
            $routeName = $request->route()?->getName() ?? $request->getPathInfo();
            
            // Clean up name or path for display
            $ruta = $routeName ?: '/';

            // Atomic increment
            $visita = VisitaPagina::firstOrCreate(
                ['ruta' => $ruta],
                ['contador' => 0]
            );
            $visita->increment('contador');
        }

        return $response;
    }
}
