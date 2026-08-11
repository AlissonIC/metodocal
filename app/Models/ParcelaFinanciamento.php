<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParcelaFinanciamento extends Model
{
    use HasFactory;

    protected $table = 'parcelas_financiamento';

    public const STATUSES = [
        'pendente' => ['Pendente', 'warning'],
        'paga' => ['Paga', 'success'],
        'cancelada' => ['Cancelada', 'secondary'],
    ];

    protected $fillable = [
        'processo_id',
        'numero',
        'vencimento',
        'valor',
        'status',
        'pago_em',
        'metodo',
        'observacoes',
        'status_alterado_por_user_id',
        'status_alterado_em',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'vencimento' => 'date',
            'pago_em' => 'datetime',
            'valor' => 'decimal:2',
            'status_alterado_em' => 'datetime',
        ];
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'processo_id');
    }

    /** Último admin que mexeu no status desta parcela. */
    public function statusAlteradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_alterado_por_user_id');
    }

    /** Atraso é derivado da data de hoje, nunca gravado. */
    public function isAtrasada(): bool
    {
        return $this->status === 'pendente' && $this->vencimento->isPast();
    }

    public function statusLabel(): string
    {
        if ($this->isAtrasada()) return 'Atrasada';
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusColor(): string
    {
        if ($this->isAtrasada()) return 'danger';
        return self::STATUSES[$this->status][1] ?? 'secondary';
    }

    /** Quantos dias de atraso — 0 quando não está atrasada. */
    public function diasAtraso(): int
    {
        return $this->isAtrasada() ? $this->vencimento->diffInDays(now()->startOfDay()) : 0;
    }
}
