<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Fatura extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'faturas';

    protected $fillable = [
        'subscription_id',
        'user_id',
        'plan_id',
        'processo_id',
        'descricao',
        'valor',
        'vencimento',
        'status',
        'pago_em',
        'estornada_em',
        'metodo',
        'gateway_payment_id',
        'gateway_preference_id',
        'gateway_refund_id',
        'link_pagamento',
        'qr_code',
        'payer_name',
        'payer_email',
        'payer_document',
        'payer_address',
        'payer_info',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'vencimento' => 'date',
            'pago_em' => 'datetime',
            'estornada_em' => 'datetime',
            'payer_address' => 'array',
            'payer_info' => 'array',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function paymentEvents(): HasMany
    {
        return $this->hasMany(PaymentEvent::class)->orderByDesc('created_at');
    }

    public function isAtrasada(): bool
    {
        return $this->status === 'pendente' && $this->vencimento->isPast();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'valor', 'pago_em', 'estornada_em', 'gateway_payment_id', 'gateway_refund_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('fatura');
    }
}
