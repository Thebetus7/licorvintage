<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Bitacora;

class BitacoraMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        // Only log authenticated users' activities
        if ($user) {
            $method = $request->method();
            $path = $request->path();
            $routeName = $request->route()?->getName();

            // Log administrative GET routes or any write operations (POST, PUT, PATCH, DELETE)
            $isAdministrativeGet = $method === 'GET' && (
                str_starts_with($path, 'productos') || 
                str_starts_with($path, 'compras') || 
                str_starts_with($path, 'proveedores') || 
                str_starts_with($path, 'caja') || 
                str_starts_with($path, 'usuarios') || 
                str_starts_with($path, 'inventario') ||
                str_starts_with($path, 'dashboard')
            );

            $isWriteOperation = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']);

            if ($isAdministrativeGet || $isWriteOperation) {
                $descripcion = $this->getHumanDescription($method, $path, $routeName);
                
                // Determine if it was allowed or failed due to authorization/validation
                $statusCode = $response->getStatusCode();
                $estado = ($statusCode >= 200 && $statusCode < 400) ? 'Permitido' : 'Denegado';

                Bitacora::create([
                    'tipo' => 'Acceso',
                    'user_id' => $user->id,
                    'descripcion' => $descripcion,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'estado' => $estado,
                    'metadata' => [
                        'ruta' => $path,
                        'metodo' => $method,
                        'route_name' => $routeName,
                        'status' => $statusCode,
                    ]
                ]);
            }
        }

        return $response;
    }

    /**
     * Translate HTTP method and path into human-readable Spanish descriptions.
     */
    private function getHumanDescription(string $method, string $path, ?string $routeName): string
    {
        if ($method === 'GET') {
            if (str_contains($path, 'productos')) return 'Visualizó la lista de productos (Administración)';
            if (str_contains($path, 'compras')) return 'Accedió al registro de compras de inventario';
            if (str_contains($path, 'proveedores')) return 'Accedió al catálogo de proveedores';
            if (str_contains($path, 'caja')) return 'Ingresó al panel de control de caja';
            if (str_contains($path, 'usuarios')) return 'Visualizó la gestión de usuarios y vendedores';
            if (str_contains($path, 'inventario')) return 'Ingresó al módulo de control de inventarios';
            if (str_contains($path, 'dashboard')) return 'Visualizó el panel de control principal (Dashboard)';
            return 'Accedió a la página: ' . $path;
        }

        $action = 'Realizó una acción en';
        if ($method === 'POST') $action = 'Creó un registro en';
        if ($method === 'PUT' || $method === 'PATCH') $action = 'Actualizó un registro en';
        if ($method === 'DELETE') $action = 'Eliminó un registro en';

        if (str_contains($path, 'productos')) return $action . ' el módulo de Productos';
        if (str_contains($path, 'compras')) return $action . ' el módulo de Compras';
        if (str_contains($path, 'proveedores')) return $action . ' el módulo de Proveedores';
        if (str_contains($path, 'caja')) return $action . ' el módulo de Caja';
        if (str_contains($path, 'usuarios')) return $action . ' el módulo de Usuarios/Vendedores';
        if (str_contains($path, 'inventario')) return $action . ' el módulo de Inventario';
        if (str_contains($path, 'ventas')) return $action . ' el registro de Ventas';

        return $action . ' la ruta: ' . $path;
    }
}
