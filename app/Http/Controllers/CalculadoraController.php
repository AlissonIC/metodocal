<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use App\Models\Servico;

class CalculadoraController extends Controller
{
    /**
     * Calculadora de quitação de dívida — mostra em tempo real quanto o cliente
     * pagaria para quitar o veículo em cada banco selecionado, considerando a
     * comissão do serviço escolhido.
     *
     * Cálculo é 100% client-side: bancos + serviços vão embarcados no HTML e o
     * JS reage a qualquer mudança nos inputs sem round-trip.
     */
    public function index()
    {
        return view('content.calculadora.index', [
            'servicos' => Servico::where('ativo', true)
                ->orderBy('nome')
                ->get(['id', 'nome', 'valor_padrao', 'descricao']),
            'bancos' => Banco::where('ativo', true)
                ->orderBy('nome')
                ->get(['id', 'nome', 'taxa']),
        ]);
    }
}
