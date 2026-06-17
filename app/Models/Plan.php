<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Plan extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'nome',
        'slug',
        'descricao',
        'tipo',
        'preco',
        'recorrencia',
        'permissions',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'preco' => 'decimal:2',
            'permissions' => 'array',
            'ativo' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nome', 'tipo', 'preco', 'recorrencia', 'ativo', 'permissions'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('plan');
    }
}
