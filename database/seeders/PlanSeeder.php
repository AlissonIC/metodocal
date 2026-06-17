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
            [
                'nome' => 'Mentoria Essencial',
                'descricao' => 'Acesso ao conteúdo básico, agenda de sessões mensais e suporte por e-mail.',
                'tipo' => 'mentorado',
                'preco' => 197.00,
                'recorrencia' => 'mensal',
                'permissions' => ['access.agenda.view', 'access.conteudos.view'],
                'ativo' => true,
            ],
            [
                'nome' => 'Mentoria Premium',
                'descricao' => 'Acesso completo a conteúdos, sessões semanais e prioridade no suporte.',
                'tipo' => 'mentorado',
                'preco' => 397.00,
                'recorrencia' => 'mensal',
                'permissions' => ['access.agenda.view', 'access.conteudos.view'],
                'ativo' => true,
            ],
            [
                'nome' => 'Licenciado Starter',
                'descricao' => 'Plano de entrada para licenciados: materiais básicos e CRM para até 50 clientes.',
                'tipo' => 'licenciado',
                'preco' => 497.00,
                'recorrencia' => 'mensal',
                'permissions' => ['access.crm.view', 'access.materiais.view', 'access.comissoes.view'],
                'ativo' => true,
            ],
            [
                'nome' => 'Licenciado Pro',
                'descricao' => 'Plano completo: todos os materiais, CRM ilimitado, comissões e suporte dedicado.',
                'tipo' => 'licenciado',
                'preco' => 1497.00,
                'recorrencia' => 'mensal',
                'permissions' => ['access.crm.view', 'access.materiais.view', 'access.comissoes.view'],
                'ativo' => true,
            ],
        ];

        foreach ($planos as $data) {
            $slug = Str::slug($data['nome']);
            Plan::updateOrCreate(
                ['slug' => $slug],
                array_merge($data, ['slug' => $slug])
            );
        }
    }
}
