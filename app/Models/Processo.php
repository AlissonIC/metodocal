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
        'cliente_user_id',
        'servico_id',
        'banco_id',
        'comprador_id',
        'nome_completo',
        'tipo_documento',
        'documento',
        'email_contato',
        'telefone_contato',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'status',
        'data_protocolo_liminar',
        'data_previsao_conclusao',
        'data_conclusao',
        'observacoes_cliente',
        'observacoes_admin',
        'valor_financiamento',
        'qtd_parcelas',
        'valor_parcela',
        'data_primeira_parcela',
        'link_pagamento_mensal',
    ];

    protected function casts(): array
    {
        return [
            'data_protocolo_liminar' => 'date',
            'data_previsao_conclusao' => 'date',
            'data_conclusao' => 'date',
            'valor_financiamento' => 'decimal:2',
            'qtd_parcelas' => 'integer',
            'valor_parcela' => 'decimal:2',
            'data_primeira_parcela' => 'date',
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

    public function banco(): BelongsTo
    {
        return $this->belongsTo(Banco::class);
    }

    /** Titular do processo com acesso ao painel (role "cliente"). */
    public function clienteUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_user_id');
    }

    public function comprador(): BelongsTo
    {
        return $this->belongsTo(Comprador::class);
    }

    public function dividas(): HasMany
    {
        return $this->hasMany(Divida::class, 'processo_id');
    }

    public function parcelas(): HasMany
    {
        return $this->hasMany(ParcelaFinanciamento::class, 'processo_id')->orderBy('numero');
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

    public function negociacoes(): HasMany
    {
        return $this->hasMany(Negociacao::class)->orderByDesc('data')->orderByDesc('id');
    }

    public function observacoes(): HasMany
    {
        return $this->hasMany(ObservacaoProcesso::class, 'processo_id')->orderByDesc('created_at');
    }

    public function veiculo(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Veiculo::class);
    }

    public function enderecoFormatado(): string
    {
        $linhas = array_filter([
            trim(($this->logradouro ?? '') . ($this->numero ? ', ' . $this->numero : '')),
            $this->complemento,
            $this->bairro,
            trim(($this->cidade ?? '') . ($this->uf ? '/' . strtoupper($this->uf) : '')),
            $this->cep,
        ]);
        return implode(' · ', $linhas) ?: '—';
    }

    public function temFinanciamento(): bool
    {
        return $this->valor_financiamento !== null
            || $this->qtd_parcelas !== null
            || $this->valor_parcela !== null
            || $this->data_primeira_parcela !== null
            || $this->banco_id !== null;
    }

    /**
     * Valor sugerido da parcela, pelo mesmo critério da Calculadora de quitação:
     * o banco aceita um percentual da dívida, soma-se a comissão do serviço e
     * divide-se pelo número de parcelas. Retorna null se faltar dado essencial.
     */
    public function parcelaCalculada(): ?float
    {
        if (! $this->valor_financiamento || ! $this->qtd_parcelas) {
            return null;
        }

        $taxa = (float) ($this->banco?->taxa ?? 0);
        $minimo = (float) $this->valor_financiamento * ($taxa / 100);
        $final = $minimo + (float) ($this->servico?->valor_padrao ?? 0);

        return round($final / $this->qtd_parcelas, 2);
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
