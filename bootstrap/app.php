<?php

use App\Http\Middleware\AuthorizeMenuAccess;
use App\Http\Middleware\EnsureProfileIsComplete;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LogResourceAccess;
use App\Http\Middleware\LogValidationErrors;
use App\Http\Middleware\TrackPageViews;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            LogResourceAccess::class,
            TrackPageViews::class,
            LogValidationErrors::class,
            EnsureProfileIsComplete::class, // Middleware de perfil completo para clientes SSO
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'menu.auth' => AuthorizeMenuAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
