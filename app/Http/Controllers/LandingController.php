<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(): View
    {
        $planos = Plan::where('ativo', true)->orderBy('preco')->get();

        return view('content.landing.index', [
            'planos' => $planos,
            'recorrenciasDisponiveis' => $planos->pluck('recorrencia')->unique()->values(),
        ]);
    }
}
