<?php

namespace App\Models;

use App\Services\NotificationQueueService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'cpf_cnpj',
        'avatar',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function currentSubscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'current_subscription_id');
    }

    public function isAtivo(): bool
    {
        return $this->status === 'ativo';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'status', 'phone', 'cpf_cnpj'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('user');
    }

    /**
     * Sobrescreve o envio default da notificação de reset de senha para
     * enfileirar via NotificationQueueService — assim aparece em /painel/notificacoes,
     * é auditável e o admin pode reenviar.
     */
    public function sendPasswordResetNotification($token): void
    {
        $minutos = (int) config('auth.passwords.users.expire', 60);
        $resetUrl = URL::to(route('password.reset', [
            'token' => $token,
            'email' => $this->email,
        ], false));

        app(NotificationQueueService::class)->queueEmailFromView(
            to: $this->email,
            subject: 'Redefinir sua senha — ' . config('variables.templateName'),
            view: 'emails.reset-password',
            viewData: [
                'user' => $this,
                'resetUrl' => $resetUrl,
                'minutosValidade' => $minutos,
            ],
            user: $this,
            related: $this,
        );
    }
}
