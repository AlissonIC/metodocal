<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class QueuedNotification extends Model
{
    use HasFactory;

    protected $table = 'notification_queue';

    protected $fillable = [
        'channel',
        'to',
        'subject',
        'body',
        'data',
        'status',
        'attempts',
        'next_attempt_at',
        'last_error',
        'sent_at',
        'user_id',
        'related_type',
        'related_id',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'sent_at' => 'datetime',
            'next_attempt_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPendente(): bool
    {
        return $this->status === 'pendente';
    }

    public function isEnviada(): bool
    {
        return $this->status === 'enviada';
    }
}
