<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class LogValidationErrors
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (ValidationException $e) {
            $errors = $e->validator->errors()->all();
            $errorsStr = implode('; ', $errors);

            $email = $request->input('email') ?? (auth()->check() ? auth()->user()->email : 'invitado');
            $uri = $request->getRequestUri();

            $resource = 'Validación';
            if (str_contains($uri, 'productos')) {
                $resource = 'Productos';
            } elseif (str_contains($uri, 'compras')) {
                $resource = 'Compras';
            } elseif (str_contains($uri, 'caja')) {
                $resource = 'Caja';
            } elseif (str_contains($uri, 'promocions') || str_contains($uri, 'promociones')) {
                $resource = 'Promociones';
            } elseif (str_contains($uri, 'usuarios')) {
                $resource = 'Usuarios';
            } elseif (str_contains($uri, 'venta') || str_contains($uri, 'checkout')) {
                $resource = 'Ventas';
            } elseif (str_contains($uri, 'proveedor')) {
                $resource = 'Proveedores';
            }

            ActivityLog::create([
                'event_type' => 'validation_failed',
                'user_id' => auth()->id(),
                'user_identity' => $email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'resource_name' => $resource,
                'visited_url' => $uri,
                'description' => "Error de validación en {$resource}. Errores: {$errorsStr}.",
            ]);

            throw $e;
        }
    }
}
