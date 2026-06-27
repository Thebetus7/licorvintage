<?php

namespace App\Http\Requests\Caja;

use Illuminate\Foundation\Http\FormRequest;

class CloseCajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'totales_caja' => ['required', 'array'],
            'totales_caja.efectivo' => ['required', 'numeric', 'min:0'],
            'totales_caja.qr' => ['required', 'numeric', 'min:0'],
            'totales_caja.tarjeta' => ['required', 'numeric', 'min:0'],
            'totales_caja.credito' => ['required', 'numeric', 'min:0'],
        ];
    }
}
