<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateCompradorRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'min:2', 'max:160'],
            'tipo_documento' => ['required', 'in:cpf,cnpj'],
            'documento' => ['required', 'string', 'min:11', 'max:20', Rule::unique('compradores', 'documento')->ignore($this->route('comprador'))],
            'email' => ['nullable', 'email', 'max:160'],
            'telefone' => ['nullable', 'string', 'max:40'],
            'observacoes' => ['nullable', 'string', 'max:3000'],
            'ativo' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'documento' => preg_replace('/\D/', '', (string) $this->documento),
            'ativo' => $this->boolean('ativo'),
        ]);
    }
}
