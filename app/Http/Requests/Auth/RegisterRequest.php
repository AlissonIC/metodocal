<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'tipo' => ['nullable', 'in:mentorado,licenciado'],
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Informe seu nome.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'E-mail inválido.',
            'email.unique' => 'Este e-mail já está em uso.',
            'tipo.in' => 'Tipo de conta inválido.',
            'password.required' => 'Informe a senha.',
            'terms.accepted' => 'Você precisa aceitar os termos.',
        ];
    }
}
