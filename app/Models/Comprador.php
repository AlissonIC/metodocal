<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Comprador extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'compradores';

    protected $fillable = [
        'nome',
        'tipo_documento',
        'documento',
        'email',
        'telefone',
        'observacoes',
        'ativo',
    ];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    public function processos(): HasMany
    {
        return $this->hasMany(Processo::class);
    }

    public function documentoFormatado(): string
    {
        $d = preg_replace('/\D/', '', (string) $this->documento);
        if ($this->tipo_documento === 'cpf' && strlen($d) === 11) {
            return substr($d, 0, 3) . '.' . substr($d, 3, 3) . '.' . substr($d, 6, 3) . '-' . substr($d, 9, 2);
        }
        if ($this->tipo_documento === 'cnpj' && strlen($d) === 14) {
            return substr($d, 0, 2) . '.' . substr($d, 2, 3) . '.' . substr($d, 5, 3) . '/' . substr($d, 8, 4) . '-' . substr($d, 12, 2);
        }
        return $this->documento;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nome', 'tipo_documento', 'documento', 'email', 'telefone', 'observacoes', 'ativo'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('comprador');
    }
}
