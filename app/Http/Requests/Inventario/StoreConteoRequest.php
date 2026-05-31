<?php

namespace App\Http\Requests\Inventario;

use Illuminate\Foundation\Http\FormRequest;

class StoreConteoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'conteos' => ['required', 'array', 'min:1'],
            'conteos.*.producto_id' => ['required', 'exists:productos,id'],
            'conteos.*.stock_fisico' => ['required', 'integer', 'min:0'],
        ];
    }
}
