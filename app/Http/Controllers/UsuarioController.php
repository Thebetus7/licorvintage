<?php

namespace App\Http\Controllers;

use App\Http\Requests\Usuario\StoreVendedorRequest;
use App\Http\Requests\Usuario\UpdateUsuarioRequest;
use App\Models\MenuItem;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Usuarios/Index', [
            'usuarios' => User::query()
                ->with('roles')
                ->latest()
                ->paginate(10),
            'menuItems' => MenuItem::all(),
        ]);
    }

    public function store(StoreVendedorRequest $request, UserManagementService $service): RedirectResponse
    {
        $service->createVendedor($request->validated());

        return back()->with('success', 'Vendedor creado correctamente.');
    }

    public function update(UpdateUsuarioRequest $request, User $usuario, UserManagementService $service): RedirectResponse
    {
        abort_if($usuario->hasRole('propietario'), 403);

        $service->update($usuario, $request->validated());

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $usuario): RedirectResponse
    {
        abort_if($usuario->is(auth()->user()) || $usuario->hasRole('propietario'), 403);

        $usuario->delete();

        return back()->with('success', 'Usuario eliminado correctamente.');
    }
}
