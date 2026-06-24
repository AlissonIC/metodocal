<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Permissions plan-gated (não dependem do role — são atribuídas via
     * SubscriptionObserver quando o usuário tem uma assinatura ativa com
     * plano que libera o módulo). Tudo que é por tipo de usuário foi migrado
     * para roles + middleware `role:X` nas rotas.
     */
    public function run(): void
    {
        $permissions = [
            'access.agenda.view',
            'access.conteudos.view',
            'access.crm.view',
            'access.materiais.view',
            'access.comissoes.view',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
