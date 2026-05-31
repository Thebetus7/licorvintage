<?php

namespace App\Http\Requests\Producto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $producto = $this->route('producto');

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'mililitros' => ['required', 'integer', 'min:1'],
            'costo' => ['required', 'numeric', 'min:0'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'string', 'max:255'],
            'fotos' => ['nullable', 'array', 'max:6'],
            'fotos.*' => ['string', 'max:255'],
            'codigo_barra' => ['required', 'string', 'max:255', Rule::unique('productos', 'codigo_barra')->ignore($producto?->id)],
            'publicado' => ['boolean'],
            'stock.min' => ['required', 'integer', 'min:0'],
            'stock.max' => ['required', 'integer', 'gte:stock.min'],
        ];
    }
}
