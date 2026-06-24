<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        // Load direct role relationship name
        $userRole = $user->rol?->nombre;

        if (!$userRole) {
            abort(403, 'User has no assigned role.');
        }

        // Roles are passed like 'propietario|vendedor'
        $allowedRoles = explode('|', $roles);

        $hasRole = false;
        foreach ($allowedRoles as $role) {
            if (strcasecmp($userRole, $role) === 0) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            abort(403, 'Unauthorized role.');
        }

        return $next($request);
    }
}
