<?php

namespace App\Http\Controllers;

use App\Models\Fatura;
use App\Models\Plan;
use App\Services\FaturaService;
use App\Services\MercadoPagoService;
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
            'fatura' => $fatura,
            'mp_configurado' => $this->mp->isConfigured(),
        ]);
    }

    public function sucesso(Request $request)
    {
        return view('content.cliente.checkout-callback', [
            'tipo' => 'sucesso',
            'titulo' => 'Pagamento aprovado!',
            'mensagem' => 'Sua assinatura está sendo ativada. Em instantes você terá acesso aos módulos contratados.',
            'icon' => 'check',
            'color' => 'success',
        ]);
    }

    public function falha(Request $request)
    {
        return view('content.cliente.checkout-callback', [
            'tipo' => 'falha',
            'titulo' => 'Pagamento não concluído',
            'mensagem' => 'Houve um problema no processamento do pagamento. Você pode tentar novamente em Minha Assinatura.',
            'icon' => 'x',
            'color' => 'danger',
        ]);
    }

    public function pendente(Request $request)
    {
        return view('content.cliente.checkout-callback', [
            'tipo' => 'pendente',
            'titulo' => 'Pagamento em análise',
            'mensagem' => 'Estamos aguardando a confirmação do pagamento. Você receberá um e-mail assim que for aprovado.',
            'icon' => 'clock',
            'color' => 'warning',
        ]);
    }
}
