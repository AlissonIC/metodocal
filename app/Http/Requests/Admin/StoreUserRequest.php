<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('access.users.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'cpf_cnpj' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'in:admin,mentorado,licenciado'],
            'status' => ['required', 'in:ativo,inativo,bloqueado'],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'password' => ['required', Password::min(8)->letters()->numbers()],
        ];
    }
}
