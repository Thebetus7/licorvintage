<?php

namespace App\Http\Controllers;

use App\Models\Promocion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromocionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('Promociones/Index', [
            'promociones' => Promocion::orderByDesc('id')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre_promo' => 'required|string|max:255',
            'codigo_promo' => 'required|string|max:100|unique:promocions,codigo_promo',
            'descuento' => 'required|numeric|min:0',
            'tipo_descuento' => 'required|string|in:porcentaje,monto',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ], [
            'nombre_promo.required' => 'El nombre de la promoción es obligatorio.',
            'codigo_promo.required' => 'El código de la promoción es obligatorio.',
            'codigo_promo.unique' => 'Este código de promoción ya existe.',
            'descuento.required' => 'El valor de descuento es obligatorio.',
            'descuento.numeric' => 'El descuento debe ser un valor numérico.',
            'tipo_descuento.required' => 'El tipo de descuento es obligatorio.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
        ]);

        Promocion::create($validated);

        return back()->with('success', 'Promoción creada correctamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Promocion $promocione): RedirectResponse
    {
        $validated = $request->validate([
            'nombre_promo' => 'required|string|max:255',
            'codigo_promo' => 'required|string|max:100|unique:promocions,codigo_promo,'.$promocione->id,
            'descuento' => 'required|numeric|min:0',
            'tipo_descuento' => 'required|string|in:porcentaje,monto',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ], [
            'nombre_promo.required' => 'El nombre de la promoción es obligatorio.',
            'codigo_promo.required' => 'El código de la promoción es obligatorio.',
            'codigo_promo.unique' => 'Este código de promoción ya existe.',
            'descuento.required' => 'El valor de descuento es obligatorio.',
            'descuento.numeric' => 'El descuento debe ser un valor numérico.',
            'tipo_descuento.required' => 'El tipo de descuento es obligatorio.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
        ]);

        $promocione->update($validated);

        return back()->with('success', 'Promoción actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Promocion $promocione): RedirectResponse
    {
        $promocione->delete();

        return back()->with('success', 'Promoción eliminada correctamente.');
    }
}
