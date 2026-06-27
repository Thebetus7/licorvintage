<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPageViews
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo procesar en peticiones GET exitosas
        if ($request->isMethod('GET') && $response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {
            $path = $request->getPathInfo();

            // Excluir endpoints de API, Livewire, debugbar, archivos comunes o peticiones internas de Jetstream/Sanctum
            if (
                ! str_starts_with($path, '/api') &&
                ! str_starts_with($path, '/sanctum') &&
                ! str_starts_with($path, '/_debugbar') &&
                ! str_starts_with($path, '/vendor') &&
                ! preg_match('/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|otf|map|json)$/i', $path)
            ) {
                rescue(fn () => PageView::incrementView($path), report: false);
            }
        }

        return $response;
    }
}
