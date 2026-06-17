<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conteudo extends Model
{
    use HasFactory;

    protected $table = 'conteudos';

    protected $fillable = [
        'titulo',
        'descricao',
        'tipo',
        'url',
        'categoria',
        'ordem',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'ordem' => 'integer',
        ];
    }

    public function progressos(): HasMany
    {
        return $this->hasMany(ProgressoConteudo::class);
    }
}
