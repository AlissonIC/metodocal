<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Despesa extends Model
{
    use HasFactory;

    protected $table = 'despesas';

    public const TIPO_FIXA = 'fixa';
    public const TIPO_UNICA = 'unica';

    protected $fillable = [
        'nome',
        'categoria',
        'descricao',
        'valor',
        'tipo',
        'dia_vencimento',
        'data_inicio',
        'encerrada_em',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'data_inicio' => 'date',
            'encerrada_em' => 'datetime',
        ];
    }

    public function ocorrencias(): HasMany
    {
        return $this->hasMany(DespesaOcorrencia::class)->orderByDesc('competencia');
    }

    public function criadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isFixa(): bool
    {
        return $this->tipo === self::TIPO_FIXA;
    }

    public function isEncerrada(): bool
    {
        return $this->encerrada_em !== null;
    }
}
