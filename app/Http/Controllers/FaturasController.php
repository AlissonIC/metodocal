<?php

namespace App\Http\Controllers;

use App\Models\Fatura;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaturasController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        $status = $request->query('status', '');
        $planId = $request->query('plan_id', '');
        $busca = trim((string) $request->query('q', ''));

        $query = Fatura::with('plan:id,nome')->where('user_id', $userId);

        if ($status === 'atrasada') {
            $query->where('status', 'pendente')->where('vencimento', '<', now()->toDateString());
        } elseif (in_array($status, ['pendente', 'paga', 'cancelada'], true)) {
            $query->where('status', $status);
            if ($status === 'pendente') {
                $query->where('vencimento', '>=', now()->toDateString());
            }
        }

        if ($planId !== '') {
            $query->where('plan_id', $planId);
        }

        if ($busca !== '') {
            $query->where(function ($q) use ($busca) {
                $q->where('id', $busca)
                  ->orWhereHas('plan', fn ($p) => $p->where('nome', 'like', '%' . $busca . '%'));
            });
        }

        $planos = Fatura::query()
            ->where('user_id', $userId)
            ->whereNotNull('plan_id')
            ->with('plan:id,nome')
            ->get()
            ->pluck('plan')
            ->filter()
            ->unique('id')
            ->sortBy('nome')
            ->values();

        return view('content.cliente.faturas', [
            'faturas' => $query->orderByDesc('created_at')->paginate(10)->withQueryString(),
            'planos' => $planos,
            'filtros' => compact('status', 'planId', 'busca'),
        ]);
    }

    public function show(Fatura $fatura, Request $request): View
    {
        abort_unless($fatura->user_id === $request->user()->id, 403);
        return view('content.cliente.fatura-detalhe', [
            'fatura' => $fatura->load('plan'),
        ]);
    }
}
