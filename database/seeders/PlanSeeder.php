<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $planos = [
            // ===== MENTORADO =====
            [
                'nome' => 'Mentoria Essencial',
                'descricao' => 'Acesso ao conteúdo básico, agenda de sessões mensais e suporte por e-mail. Ideal pra quem está começando.',
                'tipo' => 'mentorado',
                'preco' => 197.00,
                'recorrencia' => 'mensal',
                'permissions' => ['access.agenda.view'],
            ],
            [
                'nome' => 'Mentoria Plus',
                'descricao' => 'Conteúdos completos + agenda quinzenal. Suporte prioritário por e-mail.',
                'tipo' => 'mentorado',
                'preco' => 297.00,
                'recorrencia' => 'mensal',
                'permissions' => ['access.agenda.view', 'access.conteudos.view'],
            ],
            [
                'nome' => 'Mentoria Premium',
                'descricao' => 'Acesso completo: conteúdos, sessões semanais 1-a-1, prioridade no suporte e materiais exclusivos.',
                'tipo' => 'mentorado',
                'preco' => 397.00,
                'recorrencia' => 'mensal',
                'permissions' => ['access.agenda.view', 'access.conteudos.view'],
            ],
            [
                'nome' => 'Mentoria Anual',
                'descricao' => 'Todos os benefícios do Premium em pagamento anual com desconto significativo.',
                'tipo' => 'mentorado',
                'preco' => 3970.00,
                'recorrencia' => 'anual',
                'permissions' => ['access.agenda.view', 'access.conteudos.view'],
            ],

            // ===== LICENCIADO =====
            [
                'nome' => 'Licenciado Starter',
                'descricao' => 'Plano de entrada: materiais básicos e CRM para até 50 clientes.',
                'tipo' => 'licenciado',
                'preco' => 497.00,
                'recorrencia' => 'mensal',
                'permissions' => ['access.crm.view'],
            ],
            [
                'nome' => 'Licenciado Pro',
                'descricao' => 'Materiais completos, CRM ilimitado, comissões e relatórios mensais.',
                'tipo' => 'licenciado',
                'preco' => 997.00,
                'recorrencia' => 'mensal',
                'permissions' => ['access.crm.view', 'access.materiais.view', 'access.comissoes.view'],
            ],
            [
                'nome' => 'Licenciado Elite',
                'descricao' => 'Tudo do Pro + suporte dedicado e comissões diferenciadas.',
                'tipo' => 'licenciado',
                'preco' => 1497.00,
                'recorrencia' => 'mensal',
                'permissions' => ['access.crm.view', 'access.materiais.view', 'access.comissoes.view'],
            ],
            [
                'nome' => 'Licenciado Anual',
                'descricao' => 'Plano Elite em pagamento anual com desconto de 2 meses.',
                'tipo' => 'licenciado',
                'preco' => 14970.00,
                'recorrencia' => 'anual',
                'permissions' => ['access.crm.view', 'access.materiais.view', 'access.comissoes.view'],
            ],

            // Plano inativo (pra testar UI)
            [
                'nome' => 'Mentoria Legacy',
                'descricao' => 'Plano antigo mantido por contratos vigentes. Não aceita novos clientes.',
                'tipo' => 'mentorado',
                'preco' => 147.00,
                'recorrencia' => 'mensal',
                'permissions' => ['access.agenda.view'],
                'ativo' => false,
            ],
        ];

        foreach ($planos as $data) {
            $slug = Str::slug($data['nome']);
            Plan::updateOrCreate(
                ['slug' => $slug],
                array_merge(['ativo' => true], $data, ['slug' => $slug])
            );
        }
    }
}
