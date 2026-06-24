<?php

namespace App\Http\Controllers;

use App\Http\Requests\Proveedor\StoreProveedorRequest;
use App\Http\Requests\Proveedor\UpdateProveedorRequest;
use App\Models\Proveedor;
use Illuminate\Http\RedirectResponse;

class ProveedorController extends Controller
{
    public function store(StoreProveedorRequest $request): RedirectResponse
    {
        Proveedor::create($request->validated());

        return back()->with('success', 'Proveedor creado correctamente.');
    }

    public function update(UpdateProveedorRequest $request, Proveedor $proveedor): RedirectResponse
    {
        $proveedor->update($request->validated());

        return back()->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Proveedor $proveedor): RedirectResponse
    {
        $proveedor->delete();

        return back()->with('success', 'Proveedor eliminado correctamente.');
    }
}
