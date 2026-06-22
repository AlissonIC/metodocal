<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Base
            'access.dashboard.admin',
            'access.dashboard.mentorado',
            'access.dashboard.licenciado',
            'access.users.view',
            'access.users.manage',
            'access.plans.view',
            'access.plans.manage',
            'access.profile.edit',
            'access.minhaassinatura.view',

            // Mentorado - módulos liberados via plano
            'access.agenda.view',
            'access.conteudos.view',

            // Licenciado - módulos liberados via plano
            'access.crm.view',
            'access.materiais.view',
            'access.comissoes.view',

            // Admin - gerência dos módulos
            'access.sessoes.manage',
            'access.conteudos.manage',
            'access.materiais.manage',
            'access.comissoes.manage',
            'access.crm.manage',
            'access.financeiro.manage',
            'access.notificacoes.manage',
            'access.empresas-guincho.manage',
            'access.limpa-nome.manage',

            // Cliente - faturas (todos veem as próprias)
            'access.faturas.view',

            // Cliente - busca de empresas de guincho
            'access.empresas-guincho.view',

            // Cliente - processos de limpa nome
            'access.limpa-nome.view',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $admin = Role::findByName('admin');
        $admin->syncPermissions([
            'access.dashboard.admin',
            'access.profile.edit',
            'access.users.view',
            'access.users.manage',
            'access.plans.view',
            'access.plans.manage',
            'access.sessoes.manage',
            'access.conteudos.manage',
            'access.materiais.manage',
            'access.comissoes.manage',
            'access.crm.manage',
            'access.financeiro.manage',
            'access.notificacoes.manage',
            'access.empresas-guincho.manage',
            'access.empresas-guincho.view',
            'access.limpa-nome.manage',
            'access.limpa-nome.view',
        ]);

        $mentorado = Role::findByName('mentorado');
        $mentorado->syncPermissions([
            'access.dashboard.mentorado',
            'access.profile.edit',
            'access.minhaassinatura.view',
            'access.faturas.view',
            'access.empresas-guincho.view',
            'access.limpa-nome.view',
        ]);

        $licenciado = Role::findByName('licenciado');
        $licenciado->syncPermissions([
            'access.dashboard.licenciado',
            'access.profile.edit',
            'access.minhaassinatura.view',
            'access.faturas.view',
            'access.empresas-guincho.view',
            'access.limpa-nome.view',
        ]);
    }
}
