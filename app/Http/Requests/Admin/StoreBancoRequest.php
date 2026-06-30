<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseFormRequest;

class StoreBancoRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'min:2', 'max:120'],
            'cnpj' => ['required', 'string', 'min:11', 'max:20', 'unique:bancos,cnpj'],
            'taxa' => ['required', 'numeric', 'min:0', 'max:100'],
            'ativo' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cnpj' => preg_replace('/\D/', '', (string) $this->cnpj),
            'ativo' => $this->boolean('ativo'),
        ]);
    }
}
