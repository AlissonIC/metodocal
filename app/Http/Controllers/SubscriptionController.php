<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $tipo = $user->hasRole('licenciado') ? 'licenciado' : 'mentorado';

        $planosDisponiveis = Plan::where('ativo', true)->where('tipo', $tipo)->orderBy('preco')->get();

        return view('content.cliente.minha-assinatura', [
            'subscription' => $user->currentSubscription()->with('plan')->first(),
            'planos_disponiveis' => $planosDisponiveis,
            'recorrencias_disponiveis' => $planosDisponiveis->pluck('recorrencia')->unique()->values(),
        ]);
    }
}
