<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpresaGuincho extends Model
{
    use HasFactory;

    protected $table = 'empresas_guincho';

    protected $fillable = [
        'nome',
        'cnpj',
        'logo',
        'telefone',
        'whatsapp',
        'email',
        'site',
        'estado',
        'cidade',
        'cidades_atendidas',
        'endereco',
        'numero',
        'complemento',
        'bairro',
        'cep',
        'descricao',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'cidades_atendidas' => 'array',
            'ativo' => 'boolean',
        ];
    }
}
