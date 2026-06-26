<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Services\PagoFacilService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(PermissionServiceProvider::class);
    }

    public function boot(): void
    {
        Event::listen(
            Login::class,
            function (Login $event) {
                $roles = $event->user->roles->pluck('name')->toArray();
                $rolesStr = implode(', ', $roles);

                ActivityLog::create([
                    'event_type' => 'login_success',
                    'user_id' => $event->user->id,
                    'user_identity' => $event->user->email,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'resource_name' => 'Autenticación',
                    'visited_url' => null,
                    'description' => "Inicio de sesión exitoso. Usuario: {$event->user->name} (Rol: {$rolesStr})",
                ]);

                if ($event->user->hasRole('cliente')) {
                    $token = app(PagoFacilService::class)->login();
                    if ($token) {
                        cookie()->queue(cookie('pagofacil_token', $token, 480, '/', null, false));
                    }
                }
            }
        );

        Event::listen(
            Failed::class,
            function (Failed $event) {
                $email = $event->credentials['email'] ?? ($event->user ? $event->user->email : 'desconocido');
                $reason = $event->user
                    ? 'Contraseña incorrecta para el correo registrado'
                    : 'El correo electrónico no está registrado en el sistema';

                ActivityLog::create([
                    'event_type' => 'login_failed',
                    'user_id' => $event->user ? $event->user->id : null,
                    'user_identity' => $email,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'resource_name' => 'Autenticación',
                    'visited_url' => null,
                    'description' => "Intento de inicio de sesión fallido. Motivo: {$reason}. Credencial ingresada: {$email}",
                ]);
            }
        );
    }
}
