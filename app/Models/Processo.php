<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Processo extends Model
{
    use HasFactory;

    protected $table = 'processos';

    public const STATUSES = [
        'cadastrado' => ['Cadastrado', 'secondary'],
        'em_analise' => ['Em análise', 'info'],
        'consulta_valor' => ['Consulta de valor', 'info'],
        'liminar_protocolada' => ['Liminar protocolada', 'primary'],
        'aguardando_prazo_45d' => ['Aguardando prazo (45d)', 'warning'],
        'concluido' => ['Concluído', 'success'],
        'cancelado' => ['Cancelado', 'danger'],
    ];

    protected $fillable = [
        'user_id',
        'servico_id',
        'comprador_id',
        'nome_completo',
        'tipo_documento',
        'documento',
        'email_contato',
        'telefone_contato',
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

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }

    public function comprador(): BelongsTo
    {
        return $this->belongsTo(Comprador::class);
    }

    public function dividas(): HasMany
    {
        return $this->hasMany(Divida::class, 'processo_id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoProcesso::class, 'processo_id');
    }

    public function historico(): HasMany
    {
        return $this->hasMany(HistoricoProcesso::class, 'processo_id')->orderByDesc('created_at');
    }

    public function faturas(): HasMany
    {
        return $this->hasMany(Fatura::class, 'processo_id')->orderByDesc('created_at');
    }

    public function comissoes(): HasMany
    {
        return $this->hasMany(Comissao::class, 'processo_id')->orderByDesc('data_referencia');
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
}
