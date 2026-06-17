<?php

namespace App\Http\Controllers;

use App\Models\Fatura;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaturasController extends Controller
{
    public function index(Request $request): View
    {
        return view('content.cliente.faturas', [
            'faturas' => Fatura::with('plan:id,nome')
                ->where('user_id', $request->user()->id)
                ->orderByDesc('created_at')
                ->paginate(15),
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
