<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoProcesso extends Model
{
    use HasFactory;

    protected $table = 'documentos_processo';

    protected $fillable = [
        'processo_id',
        'uploaded_by_user_id',
        'categoria',
        'nome_original',
        'arquivo',
        'tamanho_bytes',
        'mime',
    ];

    protected function casts(): array
    {
        return ['tamanho_bytes' => 'integer'];
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'processo_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function tamanhoFormatado(): string
    {
        if (! $this->tamanho_bytes) return '—';
        $kb = $this->tamanho_bytes / 1024;
        if ($kb < 1024) return round($kb, 1) . ' KB';
        return round($kb / 1024, 2) . ' MB';
    }
}
