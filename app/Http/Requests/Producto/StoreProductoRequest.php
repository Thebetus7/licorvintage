<?php

namespace App\Http\Requests\Producto;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'mililitros' => ['required', 'integer', 'min:1'],
            'costo' => ['required', 'numeric', 'min:0'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'string', 'max:255'],
            'fotos' => ['nullable', 'array', 'max:6'],
            'fotos.*' => ['string', 'max:512'],
            'codigo_barra' => ['required', 'string', 'max:255', 'unique:productos,codigo_barra'],
            'publicado' => ['boolean'],
            'stock.stock' => ['required', 'integer', 'min:0'],
            'stock.min' => ['required', 'integer', 'min:0'],
            'stock.max' => ['required', 'integer', 'gte:stock.min'],
        ];
    }
}
