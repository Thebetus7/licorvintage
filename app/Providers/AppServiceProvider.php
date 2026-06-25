<?php

namespace App\Providers;

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
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            function (\Illuminate\Auth\Events\Login $event) {
                \App\Models\ActivityLog::create([
                    'event_type' => 'login_success',
                    'user_id' => $event->user->id,
                    'user_identity' => $event->user->email,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'resource_name' => null,
                    'visited_url' => null,
                ]);

                if ($event->user->hasRole('cliente')) {
                    $token = app(\App\Services\PagoFacilService::class)->login();
                    if ($token) {
                        cookie()->queue(cookie('pagofacil_token', $token, 480, '/', null, false));
                    }
                }
            }
        );

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Failed::class,
            function (\Illuminate\Auth\Events\Failed $event) {
                \App\Models\ActivityLog::create([
                    'event_type' => 'login_failed',
                    'user_id' => $event->user ? $event->user->id : null,
                    'user_identity' => $event->credentials['email'] ?? ($event->user ? $event->user->email : 'unknown'),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'resource_name' => null,
                    'visited_url' => null,
                ]);
            }
        );
    }
}
