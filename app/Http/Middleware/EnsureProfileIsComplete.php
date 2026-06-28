<?php

namespace App\Http\Middleware;

/*
|--------------------------------------------------------------------------
| Ensure Profile Is Complete Middleware
|--------------------------------------------------------------------------
|
| Intercepts authenticated clients that signed up via Google SSO.
| If their CI or phone is missing, it forces a redirect to the complete
| profile wizard to ensure we capture all mandatory business data.
|
*/

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Solo aplica a usuarios con rol cliente que tengan CI o teléfono vacío
            if ($user->hasRole('cliente') && (empty($user->ci) || empty($user->phone))) {
                // Permitir acceso solo a la ruta de completado de perfil y al cierre de sesión
                if (! $request->routeIs('profile.complete', 'profile.complete.store', 'logout')) {
                    // Si es una petición Inertia o JSON, podemos retornar un redirect o una respuesta correspondiente
                    if ($request->expectsJson() || $request->header('X-Inertia')) {
                        return response('', 409)->header('X-Inertia-Location', route('profile.complete'));
                    }

                    return redirect()->route('profile.complete');
                }
            }
        }

        return $next($request);
    }
}
