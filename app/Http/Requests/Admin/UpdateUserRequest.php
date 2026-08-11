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
            // Comprador vira um registro na tabela `compradores`, onde o documento é
            // obrigatório — sem esta regra o cadastro estourava erro de banco.
            'cpf_cnpj' => ['nullable', 'string', 'max:20', 'required_if:role,comprador'],
            'tipo_documento' => ['nullable', 'in:cpf,cnpj'],
            'data_nascimento' => ['nullable', 'date'],
            'role' => [
                'required',
                'in:admin,mentorado,licenciado,comprador,cliente',
                function ($attr, $value, $fail) use ($isSelf) {
                    if ($isSelf && $value !== 'admin' && $this->user()->hasRole('admin')) {
                        $fail('Você não pode rebaixar seu próprio acesso de admin.');
                    }
                },
            ],
            'status' => ['required', 'in:ativo,inativo,bloqueado'],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'password' => ['nullable', Password::min(8)->letters()->numbers()],

            'cep' => ['nullable', 'string', 'max:10'],
            'logradouro' => ['nullable', 'string', 'max:160'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:80'],
            'bairro' => ['nullable', 'string', 'max:80'],
            'cidade' => ['nullable', 'string', 'max:80'],
            'uf' => ['nullable', 'string', 'size:2'],

            'observacoes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'cpf_cnpj.required_if' => 'Informe o CPF/CNPJ — ele é obrigatório para o nível Comprador.',
        ];
    }
}
