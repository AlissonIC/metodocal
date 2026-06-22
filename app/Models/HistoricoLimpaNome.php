<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricoLimpaNome extends Model
{
    use HasFactory;

    protected $table = 'historico_limpa_nome';

    protected $fillable = ['processo_id', 'user_id', 'status_anterior', 'status_novo', 'observacao'];

    public function processo(): BelongsTo
    {
        return $this->belongsTo(ProcessoLimpaNome::class, 'processo_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusNovoLabel(): string
    {
        return ProcessoLimpaNome::STATUSES[$this->status_novo][0] ?? $this->status_novo;
    }

    public function statusNovoColor(): string
    {
        return ProcessoLimpaNome::STATUSES[$this->status_novo][1] ?? 'secondary';
    }
}
