<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Negociacao extends Model
{
    use HasFactory;

    protected $table = 'negociacoes';

    protected $fillable = [
        'processo_id',
        'inserida_por_user_id',
        'data',
        'assessoria',
        'resumo',
        'val_atualizado',
        'val_analise',
        'val_em_maos',
        'feedback',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'val_atualizado' => 'decimal:2',
            'val_analise' => 'decimal:2',
            'val_em_maos' => 'decimal:2',
        ];
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function inseridaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inserida_por_user_id');
    }
}
