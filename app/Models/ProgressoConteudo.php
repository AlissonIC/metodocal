<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressoConteudo extends Model
{
    protected $table = 'progresso_conteudos';

    protected $fillable = [
        'user_id',
        'conteudo_id',
        'concluido_em',
    ];

    protected function casts(): array
    {
        return [
            'concluido_em' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conteudo(): BelongsTo
    {
        return $this->belongsTo(Conteudo::class);
    }
}
