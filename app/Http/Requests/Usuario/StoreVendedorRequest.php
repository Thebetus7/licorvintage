<?php

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Rules\Password;

class StoreVendedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', new Password],
            'role' => ['required', Rule::in(['propietario', 'vendedor', 'cliente'])],
            'menus' => ['nullable', 'array'],
            'menus.*' => ['string'],
        ];
    }
}
