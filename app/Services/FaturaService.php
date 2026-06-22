<?php

namespace App\Services;

use App\Models\Fatura;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FaturaService
{
    public function __construct(private MercadoPagoService $mp) {}

    /**
     * Cria uma Subscription pendente + 1ª Fatura pendente para o plano.
     *
     * Antes de criar, cancela qualquer subscription/fatura PENDENTE anterior do
     * usuário pra não acumular registros órfãos quando o cliente clica em
     * "Contratar" várias vezes sem concluir o pagamento.
     *
     * Subscriptions ATIVAS de outro plano são mantidas — só viram canceladas
     * quando o novo pagamento for confirmado (em marcarComoPaga).
     *
     * Se o MP estiver configurado, popula link_pagamento e gateway_preference_id.
     */
    public function iniciarContratacao(User $user, Plan $plan): Fatura
    {
        return DB::transaction(function () use ($user, $plan) {
            // Cancela contratações pendentes anteriores do mesmo usuário
            $pendentes = Subscription::where('user_id', $user->id)
                ->where('status', 'pendente')
                ->get();
            foreach ($pendentes as $sub) {
                $sub->faturas()->where('status', 'pendente')->update(['status' => 'cancelada']);
                $sub->update(['status' => 'cancelada', 'canceled_at' => now()]);
            }

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'pendente',
                'started_at' => null,
                'ends_at' => null, // calculado em marcarComoPaga (a partir do pagamento real)
            ]);

            $user->forceFill(['current_subscription_id' => $subscription->id])->save();

            $fatura = Fatura::create([
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'valor' => $plan->preco,
                'vencimento' => now()->addDays(3)->toDateString(),
                'status' => 'pendente',
                // Pré-popular com os dados que já temos do cadastro do usuário —
                // o webhook do MP pode substituir depois com o que o pagador informar lá.
                'payer_name' => $user->name,
                'payer_email' => $user->email,
                'payer_document' => $user->cpf_cnpj,
            ]);

            $fatura->load(['user', 'plan']);
            $pref = $this->mp->createPreference($fatura);
            if ($pref) {
                $fatura->update([
                    'gateway_preference_id' => $pref['preference_id'],
                    'link_pagamento' => $pref['init_point'],
                ]);
                $subscription->update(['gateway_subscription_id' => $pref['preference_id']]);
            }

            return $fatura->fresh();
        });
    }

    public function marcarComoPaga(
        Fatura $fatura,
        ?string $gatewayPaymentId = null,
        ?string $metodo = 'manual'
    ): void {
        if ($fatura->status === 'paga') {
            return;
        }

        DB::transaction(function () use ($fatura, $gatewayPaymentId, $metodo) {
            $fatura->update([
                'status' => 'paga',
                'pago_em' => now(),
                'gateway_payment_id' => $gatewayPaymentId,
                'metodo' => $metodo,
            ]);

            $subscription = $fatura->subscription()->first();
            if (! $subscription) return;

            // Cancela outras subscriptions ativas do mesmo usuário —
            // o cliente assinou um novo plano, então o anterior é encerrado.
            Subscription::where('user_id', $subscription->user_id)
                ->where('id', '!=', $subscription->id)
                ->where('status', 'ativa')
                ->each(function (Subscription $sub) {
                    $sub->update(['status' => 'cancelada', 'canceled_at' => now()]);
                });

            // Ativa a subscription e recalcula a vigência a partir de AGORA
            // (não de quando foi criada — o pagamento pode ter demorado dias).
            if ($subscription->status !== 'ativa') {
                $plan = $subscription->plan()->first();
                $subscription->update([
                    'status' => 'ativa',
                    'started_at' => now(),
                    'ends_at' => $plan ? $this->calcEndsAt($plan->recorrencia) : $subscription->ends_at,
                ]);
            }

            // Garante que o current_subscription_id do user aponta pro plano pago
            $user = $subscription->user()->first();
            if ($user && $user->current_subscription_id !== $subscription->id) {
                $user->forceFill(['current_subscription_id' => $subscription->id])->save();
            }
        });
    }

    public function marcarComoCancelada(Fatura $fatura): void
    {
        $fatura->update(['status' => 'cancelada']);

        $subscription = $fatura->subscription;
        if ($subscription && $subscription->status === 'pendente') {
            $subscription->update(['status' => 'cancelada', 'canceled_at' => now()]);
        }
    }

    /**
     * Marca como estornada (refundada). Suspende a subscription se estava ativa.
     */
    public function marcarComoEstornada(Fatura $fatura, ?string $gatewayRefundId = null): void
    {
        if ($fatura->status === 'estornada') {
            return;
        }

        $fatura->update([
            'status' => 'estornada',
            'estornada_em' => now(),
            'gateway_refund_id' => $gatewayRefundId ?? $fatura->gateway_refund_id,
        ]);

        $subscription = $fatura->subscription;
        if ($subscription && $subscription->status === 'ativa') {
            $subscription->update(['status' => 'suspensa']);
        }
    }

    /**
     * Solicita estorno no Mercado Pago e marca a fatura como estornada.
     * Se o gateway não está configurado (modo dev) ou a fatura não tem
     * gateway_payment_id, registra estorno manual.
     */
    public function estornar(Fatura $fatura): array
    {
        if ($fatura->status !== 'paga') {
            return ['ok' => false, 'message' => 'Apenas faturas pagas podem ser estornadas.'];
        }

        $semGateway = ! $fatura->gateway_payment_id || ! $this->mp->isConfigured();

        if ($semGateway) {
            DB::transaction(fn () => $this->marcarComoEstornada($fatura));
            activity('financeiro_manual')
                ->performedOn($fatura)
                ->causedBy(Auth::user())
                ->withProperties([
                    'acao' => 'estorno_manual',
                    'mp_configurado' => $this->mp->isConfigured(),
                    'tinha_gateway_payment_id' => (bool) $fatura->gateway_payment_id,
                ])
                ->log('Estorno registrado manualmente (sem chamada ao gateway)');

            $msg = $this->mp->isConfigured()
                ? 'Estorno registrado manualmente (fatura sem gateway_payment_id).'
                : 'Estorno registrado manualmente (gateway Mercado Pago não configurado).';
            return ['ok' => true, 'message' => $msg];
        }

        $refund = $this->mp->refundPayment($fatura->gateway_payment_id);
        if (! $refund) {
            return ['ok' => false, 'message' => 'Falha ao processar estorno no Mercado Pago. Consulte o log.'];
        }

        DB::transaction(fn () => $this->marcarComoEstornada($fatura, $refund['id']));

        activity('financeiro_manual')
            ->performedOn($fatura)
            ->causedBy(Auth::user())
            ->withProperties(['acao' => 'estorno_gateway', 'refund' => $refund])
            ->log('Estorno solicitado ao gateway Mercado Pago');

        return ['ok' => true, 'message' => 'Estorno solicitado ao Mercado Pago.', 'refund_id' => $refund['id']];
    }

    /**
     * Troca de status manual feita pelo admin — registra ActivityLog explícito.
     * Aplica os side-effects esperados (ativar/suspender subscription).
     */
    public function mudarStatusManual(Fatura $fatura, string $novoStatus, ?string $motivo = null): array
    {
        $permitidos = ['pendente', 'paga', 'atrasada', 'cancelada', 'estornada'];
        if (! in_array($novoStatus, $permitidos, true)) {
            return ['ok' => false, 'message' => 'Status inválido.'];
        }

        $statusAnterior = $fatura->status;
        if ($statusAnterior === $novoStatus) {
            return ['ok' => false, 'message' => 'A fatura já está com este status.'];
        }

        DB::transaction(function () use ($fatura, $novoStatus) {
            switch ($novoStatus) {
                case 'paga':
                    $this->marcarComoPaga($fatura, $fatura->gateway_payment_id, $fatura->metodo ?? 'manual');
                    break;
                case 'cancelada':
                    $this->marcarComoCancelada($fatura);
                    break;
                case 'estornada':
                    $this->marcarComoEstornada($fatura);
                    break;
                case 'pendente':
                    $fatura->update(['status' => 'pendente', 'pago_em' => null]);
                    break;
                case 'atrasada':
                    $fatura->update(['status' => 'atrasada']);
                    break;
            }
        });

        activity('financeiro_manual')
            ->performedOn($fatura)
            ->causedBy(Auth::user())
            ->withProperties([
                'acao' => 'mudanca_status',
                'de' => $statusAnterior,
                'para' => $novoStatus,
                'motivo' => $motivo,
            ])
            ->log("Status alterado manualmente de '{$statusAnterior}' para '{$novoStatus}'");

        return ['ok' => true, 'message' => "Status alterado para '{$novoStatus}'."];
    }

    private function calcEndsAt(string $recorrencia): ?Carbon
    {
        return match ($recorrencia) {
            'mensal' => now()->addMonth(),
            'anual' => now()->addYear(),
            'vitalicio' => null,
            default => now()->addMonth(),
        };
    }
}
