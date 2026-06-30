<?php

namespace Database\Seeders;

use App\Models\Divida;
use App\Models\HistoricoProcesso;
use App\Models\Processo;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ProcessoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('pt_BR');

        $credores = [
            'Banco do Brasil S.A.',
            'Itaú Unibanco S.A.',
            'Bradesco S.A.',
            'Caixa Econômica Federal',
            'Santander Brasil',
            'Nu Pagamentos S.A.',
            'Cartão Mastercard',
            'Cartão Visa Cred',
            'Telefônica Brasil (Vivo)',
            'TIM S.A.',
            'Claro S.A.',
            'Casas Bahia',
            'Magazine Luiza',
            'Renner',
            'Lojas Americanas',
            'Crefisa',
            'BV Financeira',
            'Sicredi',
            'CredSystem',
            'Avon Cosméticos',
        ];

        $servicos = Servico::pluck('id', 'slug');
        if ($servicos->isEmpty()) {
            return;
        }

        // 50% Limpa Nome, 30% Aquisição, 20% Negociação
        $sorteioServico = array_merge(
            array_fill(0, 5, $servicos['limpa-nome'] ?? $servicos->first()),
            array_fill(0, 3, $servicos['aquisicao-divida'] ?? $servicos->first()),
            array_fill(0, 2, $servicos['negociacao-divida'] ?? $servicos->first()),
        );

        $clientes = User::role('mentorado')->get()
            ->merge(User::role('licenciado')->get());

        foreach ($clientes as $user) {
            $qtdProc = rand(0, 3);
            for ($i = 0; $i < $qtdProc; $i++) {
                $tipoDoc = rand(0, 100) < 80 ? 'cpf' : 'cnpj';
                $servicoId = $sorteioServico[array_rand($sorteioServico)];
                $createdAt = Carbon::now()->subDays(rand(7, 365));

                $diasDesde = $createdAt->diffInDays(Carbon::now());
                $status = match (true) {
                    $diasDesde < 3 => 'cadastrado',
                    $diasDesde < 10 => $faker->randomElement(['em_analise', 'consulta_valor']),
                    $diasDesde < 30 => $faker->randomElement(['consulta_valor', 'liminar_protocolada']),
                    $diasDesde < 75 => $faker->randomElement(['liminar_protocolada', 'aguardando_prazo_45d']),
                    default => $faker->randomElement(['concluido', 'concluido', 'concluido', 'cancelado']),
                };

                $protocolo = in_array($status, ['liminar_protocolada', 'aguardando_prazo_45d', 'concluido'])
                    ? $createdAt->copy()->addDays(rand(5, 20))
                    : null;
                $previsao = $protocolo ? $protocolo->copy()->addDays(45) : null;
                $conclusao = $status === 'concluido' ? ($previsao?->copy()->addDays(rand(0, 15)) ?? Carbon::now()->subDays(rand(1, 30))) : null;

                $proc = Processo::create([
                    'user_id' => $user->id,
                    'servico_id' => $servicoId,
                    'nome_completo' => $tipoDoc === 'cpf' ? $faker->name() : $faker->company(),
                    'tipo_documento' => $tipoDoc,
                    'documento' => $tipoDoc === 'cpf' ? $faker->cpf() : $faker->cnpj(),
                    'email_contato' => $faker->safeEmail(),
                    'telefone_contato' => $faker->cellphoneNumber(),
                    'status' => $status,
                    'data_protocolo_liminar' => $protocolo?->toDateString(),
                    'data_previsao_conclusao' => $previsao?->toDateString(),
                    'data_conclusao' => $conclusao?->toDateString(),
                    'observacoes_cliente' => rand(0, 100) < 70 ? $faker->paragraph(rand(1, 3)) : null,
                    'observacoes_admin' => rand(0, 100) < 50 ? $faker->paragraph(rand(1, 2)) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $conclusao ?? $protocolo ?? $createdAt,
                ]);

                $qtdDividas = rand(1, 5);
                for ($d = 0; $d < $qtdDividas; $d++) {
                    Divida::create([
                        'processo_id' => $proc->id,
                        'credor' => $faker->randomElement($credores),
                        'valor' => $faker->randomFloat(2, 100, 50000),
                        'descricao' => rand(0, 100) < 60 ? $faker->sentence(rand(4, 10)) : null,
                    ]);
                }

                HistoricoProcesso::create([
                    'processo_id' => $proc->id,
                    'user_id' => $user->id,
                    'status_anterior' => null,
                    'status_novo' => 'cadastrado',
                    'observacao' => 'Processo cadastrado pelo cliente.',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $transicoes = $this->transicoesPara($status);
                $atual = 'cadastrado';
                $whenCursor = $createdAt->copy();
                foreach ($transicoes as $proximo) {
                    $whenCursor = $whenCursor->copy()->addDays(rand(2, 12));
                    HistoricoProcesso::create([
                        'processo_id' => $proc->id,
                        'user_id' => null,
                        'status_anterior' => $atual,
                        'status_novo' => $proximo,
                        'observacao' => $this->observacaoTransicao($proximo, $faker),
                        'created_at' => $whenCursor,
                        'updated_at' => $whenCursor,
                    ]);
                    $atual = $proximo;
                }
            }
        }
    }

    private function transicoesPara(string $statusFinal): array
    {
        $fluxo = ['cadastrado', 'em_analise', 'consulta_valor', 'liminar_protocolada', 'aguardando_prazo_45d', 'concluido'];

        if ($statusFinal === 'cancelado') {
            return ['em_analise', 'cancelado'];
        }

        $idx = array_search($statusFinal, $fluxo, true);
        if ($idx === false || $idx === 0) {
            return [];
        }
        return array_slice($fluxo, 1, $idx);
    }

    private function observacaoTransicao(string $status, \Faker\Generator $faker): string
    {
        return match ($status) {
            'em_analise' => 'Processo encaminhado para análise jurídica.',
            'consulta_valor' => 'Consulta de valores em andamento junto aos credores.',
            'liminar_protocolada' => 'Liminar protocolada no fórum.',
            'aguardando_prazo_45d' => 'Aguardando decorrência do prazo legal de 45 dias.',
            'concluido' => 'Processo concluído com sucesso.',
            'cancelado' => 'Processo cancelado por solicitação. ' . $faker->sentence(),
            default => $faker->sentence(),
        };
    }
}
