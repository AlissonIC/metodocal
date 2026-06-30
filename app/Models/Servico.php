<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Servico extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'servicos';

    protected $fillable = [
        'nome',
        'slug',
        'descricao',
        'valor_padrao',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'valor_padrao' => 'decimal:2',
            'ativo' => 'boolean',
        ];
    }

    public function processos(): HasMany
    {
        return $this->hasMany(Processo::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nome', 'slug', 'descricao', 'valor_padrao', 'ativo'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('servico');
    }
}
