<?php

namespace Database\Seeders;

use App\Models\Sessao;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SessaoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('pt_BR');

        $titulos = [
            'Avaliação inicial e plano de ação',
            'Definição de objetivos mensais',
            'Revisão de carteira de veículos',
            'Análise de oportunidades de leilão',
            'Estratégia de aquisição',
            'Revisão jurídica de documentos',
            'Acompanhamento de processos',
            'Planejamento financeiro',
            'Calibragem de método',
            'Sessão de feedback',
            'Mentoria 1-a-1: dúvidas técnicas',
            'Análise de caso real',
        ];

        // Pega só mentorados com assinatura ativa
        $mentorados = User::role('mentorado')
            ->whereNotNull('current_subscription_id')
            ->get();

        foreach ($mentorados as $user) {
            // 4 a 10 sessões por mentorado (passadas + futuras)
            $qtd = rand(4, 10);
            for ($i = 0; $i < $qtd; $i++) {
                $offset = rand(-180, 60); // sessões passadas e futuras (-180 a +60 dias)
                $when = Carbon::now()->addDays($offset)->setTime(rand(9, 18), $faker->randomElement([0, 30]));

                $isPast = $when->lt(Carbon::now());

                if ($isPast) {
                    $r = rand(1, 100);
                    $status = $r <= 80 ? 'concluida' : ($r <= 92 ? 'cancelada' : 'agendada');
                } else {
                    $status = 'agendada';
                }

                Sessao::create([
                    'user_id' => $user->id,
                    'titulo' => $faker->randomElement($titulos),
                    'descricao' => $faker->paragraph(rand(2, 4)),
                    'scheduled_at' => $when,
                    'duracao_minutos' => $faker->randomElement([30, 45, 60, 60, 60, 90]),
                    'link_reuniao' => 'https://meet.google.com/' . substr(md5($user->id . $i), 0, 11),
                    'status' => $status,
                    'notas' => $status === 'concluida' ? $faker->paragraph(rand(1, 3)) : null,
                ]);
            }
        }
    }
}
