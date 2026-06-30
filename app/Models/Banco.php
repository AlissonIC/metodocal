<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Banco extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'bancos';

    protected $fillable = [
        'nome',
        'cnpj',
        'taxa',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'taxa' => 'decimal:2',
            'ativo' => 'boolean',
        ];
    }

    public function cnpjFormatado(): string
    {
        $d = preg_replace('/\D/', '', (string) $this->cnpj);
        if (strlen($d) !== 14) return $this->cnpj;
        return substr($d, 0, 2) . '.' . substr($d, 2, 3) . '.' . substr($d, 5, 3) . '/' . substr($d, 8, 4) . '-' . substr($d, 12, 2);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nome', 'cnpj', 'taxa', 'ativo'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('banco');
    }
}
