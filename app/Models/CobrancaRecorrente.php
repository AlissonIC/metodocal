<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Molde de uma cobrança que se repete todo mês até ser encerrada.
 * Cada mês gerado vira uma linha em `faturas`.
 */
class CobrancaRecorrente extends Model
{
    use HasFactory;

    protected $table = 'cobrancas_recorrentes';

    protected $fillable = [
        'processo_id',
        'user_id',
        'descricao',
        'valor',
        'dia_vencimento',
        'data_inicio',
        'encerrada_em',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'dia_vencimento' => 'integer',
            'data_inicio' => 'date',
            'encerrada_em' => 'datetime',
        ];
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'processo_id');
    }

    public function faturas(): HasMany
    {
        return $this->hasMany(Fatura::class, 'cobranca_recorrente_id')->orderBy('competencia');
    }

    public function isAtiva(): bool
    {
        return $this->encerrada_em === null;
    }

    public function statusLabel(): string
    {
        return $this->isAtiva() ? 'Ativa' : 'Encerrada';
    }

    public function statusColor(): string
    {
        return $this->isAtiva() ? 'success' : 'secondary';
    }
}
