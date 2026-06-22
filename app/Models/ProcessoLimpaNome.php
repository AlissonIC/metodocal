<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessoLimpaNome extends Model
{
    use HasFactory;

    protected $table = 'processos_limpa_nome';

    public const STATUSES = [
        'cadastrado' => ['Cadastrado', 'secondary'],
        'em_analise' => ['Em análise', 'info'],
        'consulta_valor' => ['Consulta de valor', 'info'],
        'liminar_protocolada' => ['Liminar protocolada', 'primary'],
        'aguardando_prazo_45d' => ['Aguardando prazo (45d)', 'warning'],
        'concluido' => ['Concluído', 'success'],
        'cancelado' => ['Cancelado', 'danger'],
    ];

    public const TIPOS = [
        'limpa_nome' => 'Limpa Nome',
        'aquisicao' => 'Aquisição de Dívida',
        'negociacao_divida' => 'Negociação de Dívida',
    ];

    protected $fillable = [
        'user_id',
        'nome_completo',
        'tipo_documento',
        'documento',
        'email_contato',
        'telefone_contato',
        'tipo',
        'status',
        'data_protocolo_liminar',
        'data_previsao_conclusao',
        'data_conclusao',
        'observacoes_cliente',
        'observacoes_admin',
    ];

    protected function casts(): array
    {
        return [
            'data_protocolo_liminar' => 'date',
            'data_previsao_conclusao' => 'date',
            'data_conclusao' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dividas(): HasMany
    {
        return $this->hasMany(DividaLimpaNome::class, 'processo_id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoLimpaNome::class, 'processo_id');
    }

    public function historico(): HasMany
    {
        return $this->hasMany(HistoricoLimpaNome::class, 'processo_id')->orderByDesc('created_at');
    }

    public function isEditavelPeloCliente(): bool
    {
        return $this->status === 'cadastrado';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUSES[$this->status][1] ?? 'secondary';
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }
}
