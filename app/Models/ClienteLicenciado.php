<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClienteLicenciado extends Model
{
    use HasFactory;

    protected $table = 'clientes_licenciado';

    protected $fillable = [
        'licensed_by_user_id',
        'nome',
        'email',
        'telefone',
        'cpf_cnpj',
        'endereco',
        'status',
        'notas',
    ];

    public function licenciado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'licensed_by_user_id');
    }

    public function comissoes(): HasMany
    {
        return $this->hasMany(Comissao::class, 'cliente_id');
    }
}
