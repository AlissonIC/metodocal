<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        $targetId = $this->route('user')?->id;
        $isSelf = $targetId === $this->user()->id;

        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($targetId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'cpf_cnpj' => ['nullable', 'string', 'max:20'],
            'role' => [
                'required',
                'in:admin,mentorado,licenciado',
                function ($attr, $value, $fail) use ($isSelf) {
                    if ($isSelf && $value !== 'admin' && $this->user()->hasRole('admin')) {
                        $fail('Você não pode rebaixar seu próprio acesso de admin.');
                    }
                },
            ],
            'status' => ['required', 'in:ativo,inativo,bloqueado'],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'password' => ['nullable', Password::min(8)->letters()->numbers()],
        ];
    }
}
