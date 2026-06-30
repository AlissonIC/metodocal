<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Divida extends Model
{
    use HasFactory;

    protected $table = 'dividas';

    protected $fillable = ['processo_id', 'credor', 'valor', 'descricao'];

    protected function casts(): array
    {
        return ['valor' => 'decimal:2'];
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'processo_id');
    }
}
