<?php

namespace App\Http\Controllers;

use App\Models\Fatura;
use App\Models\Plan;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $tipo = $user->hasRole('licenciado') ? 'licenciado' : 'mentorado';

        return view('content.cliente.minha-assinatura', [
            'user' => $user,
            'subscription' => $user->currentSubscription()->with('plan')->first(),
            'historico' => $user->subscriptions()->with('plan:id,nome,tipo,preco')->latest()->take(20)->get(),
            'planos_disponiveis' => Plan::where('ativo', true)->where('tipo', $tipo)->orderBy('preco')->get(),
            'faturas_recentes' => Fatura::with('plan:id,nome')->where('user_id', $user->id)->orderByDesc('created_at')->take(5)->get(),
        ]);
    }
}
