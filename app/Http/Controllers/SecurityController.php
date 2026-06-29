<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class SecurityController extends Controller
{
    /**
     * Display the security dashboard including roles, menu items, activity logs, and statistics.
     */
    public function index(): Response
    {
        // 1. Obtener todos los menús y roles de la base de datos
        $menuItems = MenuItem::orderBy('id')->get();
        $roles = Role::orderBy('name')->get();

        // 2. Obtener estadísticas de inicio de sesión
        $totalLogins = ActivityLog::where('event_type', 'login_success')->count();
        $failedLogins = ActivityLog::where('event_type', 'login_failed')->count();
        $uniqueIps = ActivityLog::distinct('ip_address')->count('ip_address');

        // 3. Ranking de recursos más visitados
        $mostVisitedResources = ActivityLog::where('event_type', 'resource_access')
            ->select('resource_name', DB::raw('count(*) as total'))
            ->groupBy('resource_name')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        // 4. Bitácora de actividades (Paginada)
        $activityLogs = ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // 5. Todos los usuarios para filtros de reportes
        $users = User::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Seguridad/Index', [
            'menuItems' => $menuItems,
            'roles' => $roles,
            'stats' => [
                'total_logins' => $totalLogins,
                'failed_logins' => $failedLogins,
                'unique_ips' => $uniqueIps,
            ],
            'mostVisitedResources' => $mostVisitedResources,
            'activityLogs' => $activityLogs,
            'users' => $users,
        ]);
    }

    /**
     * Update the Access Matrix (roles authorized for each menu item).
     */
    public function updateMatrix(Request $request): RedirectResponse
    {
        $request->validate([
            'matrix' => 'required|array',
            'matrix.*' => 'array',
        ]);

        $matrix = $request->input('matrix'); // [menu_item_id => [role1, role2, ...]]

        DB::beginTransaction();
        try {
            foreach ($matrix as $itemId => $authorizedRoles) {
                $menuItem = MenuItem::find($itemId);
                if ($menuItem) {
                    // Filtrar roles vacíos y limpiar
                    $rolesToSave = array_values(array_filter($authorizedRoles, function ($role) {
                        return ! empty($role);
                    }));

                    $menuItem->update([
                        'roles' => $rolesToSave,
                    ]);
                }
            }
            DB::commit();

            return back()->with('success', 'Matriz de acceso actualizada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Ocurrió un error al actualizar la matriz: '.$e->getMessage());
        }
    }
}
