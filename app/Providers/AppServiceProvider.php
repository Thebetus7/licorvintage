<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use App\Models\Bitacora;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Removed Spatie PermissionServiceProvider registration since roles are simplified
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Audit successful logins
        Event::listen(Login::class, function (Login $event) {
            Bitacora::create([
                'tipo' => 'Login',
                'user_id' => $event->user->id,
                'descripcion' => 'Inicio de sesión exitoso: ' . $event->user->nombre,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'estado' => 'Exitoso',
                'metadata' => [
                    'email' => $event->user->email,
                ]
            ]);
        });

        // 2. Audit failed logins
        Event::listen(Failed::class, function (Failed $event) {
            Bitacora::create([
                'tipo' => 'Login',
                'user_id' => null,
                'descripcion' => 'Intento de inicio de sesión fallido para el correo: ' . ($event->credentials['email'] ?? 'N/A'),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'estado' => 'Fallido',
                'metadata' => [
                    'credentials_email' => $event->credentials['email'] ?? null,
                ]
            ]);
        });
    }
}
