<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObservacaoProcesso extends Model
{
    use HasFactory;

    protected $table = 'observacoes_processo';

    protected $fillable = [
        'processo_id',
        'inserida_por_user_id',
        'editada_por_user_id',
        'resumo',
        'descricao',
        'editada_em',
        'anexo_arquivo',
        'anexo_nome_original',
        'anexo_mime',
        'anexo_tamanho_bytes',
    ];

    protected function casts(): array
    {
        return [
            'editada_em' => 'datetime',
            'anexo_tamanho_bytes' => 'integer',
        ];
    }

    public function hasAnexo(): bool
    {
        return ! empty($this->anexo_arquivo);
    }

    public function anexoIsImage(): bool
    {
        return $this->hasAnexo() && str_starts_with((string) $this->anexo_mime, 'image/');
    }

    public function anexoTamanhoFormatado(): string
    {
        $b = (int) $this->anexo_tamanho_bytes;
        if ($b <= 0) return '';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($b >= 1024 && $i < count($units) - 1) { $b /= 1024; $i++; }
        return number_format($b, $b < 10 && $i > 0 ? 1 : 0, ',', '.') . ' ' . $units[$i];
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function inseridaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inserida_por_user_id');
    }

    public function editadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editada_por_user_id');
    }
}
