<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DespesaOcorrencia extends Model
{
    use HasFactory;

    protected $table = 'despesa_ocorrencias';

    public const STATUSES = [
        'pendente' => ['Pendente', 'warning'],
        'paga' => ['Paga', 'success'],
        'cancelada' => ['Cancelada', 'secondary'],
    ];

    protected $fillable = [
        'despesa_id',
        'competencia',
        'vencimento',
        'valor',
        'status',
        'pago_em',
        'metodo',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'competencia' => 'date',
            'vencimento' => 'date',
            'pago_em' => 'datetime',
            'valor' => 'decimal:2',
        ];
    }

    public function despesa(): BelongsTo
    {
        return $this->belongsTo(Despesa::class);
    }

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
}
