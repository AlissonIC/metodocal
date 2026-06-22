<?php

namespace App\Http\Controllers;

use App\Models\Fatura;
use App\Models\Plan;
use App\Services\FaturaService;
use App\Services\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private FaturaService $faturaService,
        private MercadoPagoService $mp,
    ) {}

    public function contratar(Request $request, Plan $plan)
    {
        $user = $request->user();

        if (! $plan->ativo) {
            return redirect()->route('subscription.view')->with('status', 'Este plano não está mais disponível.');
        }

        $userTipo = $user->hasRole('licenciado') ? 'licenciado' : 'mentorado';
        if ($plan->tipo !== $userTipo) {
            return redirect()->route('subscription.view')->with('status', 'Este plano não é compatível com o seu nível de acesso.');
        }

        $fatura = $this->faturaService->iniciarContratacao($user, $plan);

        if ($fatura->link_pagamento) {
            return redirect()->away($fatura->link_pagamento);
        }

        return redirect()->route('checkout.aguardando', $fatura);
    }

    public function aguardando(Fatura $fatura, Request $request): View
    {
        abort_unless($fatura->user_id === $request->user()->id, 403);
        return view('content.cliente.checkout-aguardando', [
            'fatura' => $fatura->load('plan'),
            'mp_configurado' => $this->mp->isConfigured(),
        ]);
    }

    /**
     * Endpoint JSON consultado pelo polling da página de aguardando.
     * Retorna o status atual da fatura para o front detectar a confirmação
     * do pagamento sem precisar recarregar a página inteira.
     */
    public function status(Fatura $fatura, Request $request): JsonResponse
    {
        abort_unless($fatura->user_id === $request->user()->id, 403);

        $statusEfetivo = $fatura->isAtrasada() ? 'atrasada' : $fatura->status;

        return response()->json([
            'id' => $fatura->id,
            'status' => $statusEfetivo,
            'pago_em' => $fatura->pago_em?->toIso8601String(),
            'metodo' => $fatura->metodo,
            'subscription_status' => $fatura->subscription?->status,
            'is_final' => in_array($fatura->status, ['paga', 'cancelada', 'estornada'], true),
        ]);
    }

    public function sucesso(Request $request): View
    {
        return view('content.cliente.checkout-callback', $this->resolveCallback($request, 'sucesso'));
    }

    public function falha(Request $request): View
    {
        return view('content.cliente.checkout-callback', $this->resolveCallback($request, 'falha'));
    }

    public function pendente(Request $request): View
    {
        return view('content.cliente.checkout-callback', $this->resolveCallback($request, 'pendente'));
    }

    /**
     * Resolve a fatura a partir do external_reference (fatura_X) que o MP
     * devolve nas back_urls e monta os dados reais do callback.
     */
    private function resolveCallback(Request $request, string $tipo): array
    {
        $extRef = (string) $request->query('external_reference', '');
        $fatura = null;
        if (str_starts_with($extRef, 'fatura_')) {
            $id = (int) substr($extRef, 7);
            $fatura = Fatura::with('plan')
                ->where('id', $id)
                ->where('user_id', $request->user()->id)
                ->first();
        }

        $defaults = [
            'sucesso' => [
                'titulo' => 'Pagamento aprovado!',
                'mensagem' => 'Sua assinatura está sendo ativada. Em instantes você terá acesso aos módulos contratados.',
                'icon' => 'check',
                'color' => 'success',
            ],
            'pendente' => [
                'titulo' => 'Pagamento em análise',
                'mensagem' => 'Estamos aguardando a confirmação do pagamento. Você pode acompanhar o status na sua página de faturas.',
                'icon' => 'clock',
                'color' => 'warning',
            ],
            'falha' => [
                'titulo' => 'Pagamento não concluído',
                'mensagem' => 'Houve um problema no processamento. Você pode tentar novamente em Minha Assinatura.',
                'icon' => 'x',
                'color' => 'danger',
            ],
        ];

        return array_merge($defaults[$tipo], [
            'tipo' => $tipo,
            'fatura' => $fatura,
            'mp_status' => (string) $request->query('status', ''),
            'mp_payment_id' => (string) $request->query('payment_id', ''),
        ]);
    }
}
