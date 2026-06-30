<?php

namespace Database\Seeders;

use App\Models\Servico;
use Illuminate\Database\Seeder;

class ServicoSeeder extends Seeder
{
    public function run(): void
    {
        $servicos = [
            [
                'nome' => 'Limpa Nome',
                'slug' => 'limpa-nome',
                'descricao' => 'Processo judicial para remoção de restrições e limpeza do nome em órgãos de proteção ao crédito.',
                'valor_padrao' => 1200.00,
                'ativo' => true,
            ],
            [
                'nome' => 'Aquisição de Dívida',
                'slug' => 'aquisicao-divida',
                'descricao' => 'Aquisição de dívida do credor original com posterior negociação direta com o devedor.',
                'valor_padrao' => 1800.00,
                'ativo' => true,
            ],
            [
                'nome' => 'Negociação de Dívida',
                'slug' => 'negociacao-divida',
                'descricao' => 'Intermediação na negociação de dívidas com credores para obter melhores condições de pagamento.',
                'valor_padrao' => 900.00,
                'ativo' => true,
            ],
        ];

        foreach ($servicos as $servico) {
            Servico::updateOrCreate(['slug' => $servico['slug']], $servico);
        }
    }
}
