<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DividaLimpaNome extends Model
{
    use HasFactory;

    protected $table = 'dividas_limpa_nome';

    protected $fillable = ['processo_id', 'credor', 'valor', 'descricao'];

    protected function casts(): array
    {
        return ['valor' => 'decimal:2'];
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(ProcessoLimpaNome::class, 'processo_id');
    }
}
