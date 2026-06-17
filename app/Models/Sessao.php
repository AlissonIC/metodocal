<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sessao extends Model
{
    use HasFactory;

    protected $table = 'sessoes';

    protected $fillable = [
        'user_id',
        'titulo',
        'descricao',
        'scheduled_at',
        'duracao_minutos',
        'link_reuniao',
        'status',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'duracao_minutos' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
