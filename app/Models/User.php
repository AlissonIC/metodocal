<?php

namespace App\Models;

use App\Services\NotificationQueueService;
use Illuminate\Database\Eloquent\Builder;
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
        'tipo_documento',
        'data_nascimento',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'observacoes',
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
            'data_nascimento' => 'date',
            'password' => 'hashed',
        ];
    }

    public function comprador(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Comprador::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function processos(): HasMany
    {
        return $this->hasMany(Processo::class);
    }

    /**
     * Parcelas vencidas e ainda em aberto do cliente, atravessando os processos dele:
     * tanto o carnê do financiamento quanto as cobranças parceladas com a empresa.
     * É o que sustenta a tag "Inadimplente" na listagem de usuários.
     */
    public function scopeWithParcelasAtrasadasCount(Builder $query): Builder
    {
        return $query->withCount([
            'processos as parcelas_atrasadas_count' => fn ($q) => $q
                ->join('parcelas_financiamento', 'parcelas_financiamento.processo_id', '=', 'processos.id')
                ->where('parcelas_financiamento.status', 'pendente')
                ->where('parcelas_financiamento.vencimento', '<', now()->startOfDay()),
            'processos as faturas_atrasadas_count' => fn ($q) => $q
                ->join('faturas', 'faturas.processo_id', '=', 'processos.id')
                ->whereIn('faturas.status', ['pendente', 'atrasada'])
                ->where('faturas.vencimento', '<', now()->startOfDay()),
        ]);
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
