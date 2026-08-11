<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricoProcesso extends Model
{
    use HasFactory;

    protected $table = 'historico_processo';

    public const ACAO_CREATED  = 'created';
    public const ACAO_UPDATED  = 'updated';
    public const ACAO_DELETED  = 'deleted';
    public const ACAO_STATUS   = 'status_changed';

    /**
     * Metadados visuais por entidade — usados no timeline do processo.
     * [ícone tabler, cor semântica]
     */
    public const ENTIDADE_META = [
        'processo'   => ['tabler-folder',        'primary'],
        'status'     => ['tabler-refresh',       'info'],
        'observacao' => ['tabler-note',          'secondary'],
        'fatura'     => ['tabler-file-invoice',  'warning'],
        'comissao'   => ['tabler-cash',          'success'],
        'negociacao' => ['tabler-message-2',     'info'],
        'documento'  => ['tabler-file',          'primary'],
        'parcela'    => ['tabler-calendar-dollar', 'warning'],
    ];

    public const ACAO_LABELS = [
        self::ACAO_CREATED => 'Criou',
        self::ACAO_UPDATED => 'Editou',
        self::ACAO_DELETED => 'Excluiu',
        self::ACAO_STATUS  => 'Alterou status',
    ];

    protected $fillable = [
        'processo_id',
        'user_id',
        'acao',
        'entidade',
        'entidade_id',
        'status_anterior',
        'status_novo',
        'observacao',
    ];

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'processo_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusNovoLabel(): string
    {
        return Processo::STATUSES[$this->status_novo][0] ?? ($this->status_novo ?? '');
    }

    public function statusNovoColor(): string
    {
        return Processo::STATUSES[$this->status_novo][1] ?? 'secondary';
    }

    public function icone(): string
    {
        return self::ENTIDADE_META[$this->entidade][0] ?? 'tabler-activity';
    }

    public function cor(): string
    {
        if ($this->entidade === 'status') {
            return $this->statusNovoColor();
        }
        return self::ENTIDADE_META[$this->entidade][1] ?? 'secondary';
    }

    public function acaoLabel(): string
    {
        return self::ACAO_LABELS[$this->acao] ?? 'Registrou';
    }
}
