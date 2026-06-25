<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'csrf_token' => fn () => csrf_token(),
            'auth' => [
                'user' => $request->user()?->loadMissing('roles'),
                'roles' => $request->user()?->getRoleNames()->values()->all() ?? [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'menu' => fn () => $this->getMenuForUser($request->user()),
            'pagofacil_token' => fn () => $request->cookie('pagofacil_token'),
            'page_views_count' => fn () => \App\Models\PageView::where('url_path', $request->getPathInfo() === '/' ? '/' : rtrim($request->getPathInfo(), '/'))->value('views_count') ?? 1,
        ];
    }

    protected function getMenuForUser($user): array
    {
        if (!$user) {
            return [];
        }

        // Obtener todos los ítems de menú de la base de datos
        $menuItems = \App\Models\MenuItem::all();

        $menu = [];

        // Si el usuario tiene menús personalizados asignados individualmente en la base de datos
        if (is_array($user->menus) && count($user->menus) > 0) {
            foreach ($menuItems as $item) {
                if (in_array($item->route_name, $user->menus)) {
                    $menu[] = [
                        'label' => $item->label,
                        'routeName' => $item->route_name,
                    ];
                }
            }
            return $menu;
        }

        // Si no tiene personalización, se cargan por defecto basándose en sus roles
        foreach ($menuItems as $item) {
            $hasAccess = false;
            foreach ($item->roles as $role) {
                if ($user->hasRole($role)) {
                    $hasAccess = true;
                    break;
                }
            }

            if ($hasAccess) {
                $menu[] = [
                    'label' => $item->label,
                    'routeName' => $item->route_name,
                ];
            }
        }

        return $menu;
    }
}
