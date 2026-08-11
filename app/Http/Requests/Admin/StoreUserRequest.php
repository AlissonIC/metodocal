<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            // Comprador vira um registro na tabela `compradores`, onde o documento é
            // obrigatório — sem esta regra o cadastro estourava erro de banco.
            'cpf_cnpj' => ['nullable', 'string', 'max:20', 'required_if:role,comprador'],
            'tipo_documento' => ['nullable', 'in:cpf,cnpj'],
            'data_nascimento' => ['nullable', 'date'],
            'role' => ['required', 'in:admin,mentorado,licenciado,comprador,cliente'],
            'status' => ['required', 'in:ativo,inativo,bloqueado'],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'password' => ['required', Password::min(8)->letters()->numbers()],

            // Endereço (todo opcional)
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
