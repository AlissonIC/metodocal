<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comissao extends Model
{
    use HasFactory;

    protected $table = 'comissoes';

    public const TIPOS = [
        'a_receber' => ['A receber', 'success'],
        'a_pagar' => ['A pagar', 'warning'],
    ];

    protected $fillable = [
        'licensed_by_user_id',
        'cliente_id',
        'processo_id',
        'descricao',
        'valor',
        'tipo',
        'data_referencia',
        'status',
        'pago_em',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'data_referencia' => 'date',
            'pago_em' => 'datetime',
        ];
    }

    public function licenciado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'licensed_by_user_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(ClienteLicenciado::class, 'cliente_id');
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo][0] ?? $this->tipo;
    }

    public function tipoColor(): string
    {
        return self::TIPOS[$this->tipo][1] ?? 'secondary';
    }
}
