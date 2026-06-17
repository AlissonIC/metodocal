<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comissao extends Model
{
    use HasFactory;

    protected $table = 'comissoes';

    protected $fillable = [
        'licensed_by_user_id',
        'cliente_id',
        'descricao',
        'valor',
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
}
