<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Remove do DB as permissions que viraram role-based (middleware `role:X`).
     * Mantém só as plan-gated, que continuam sendo distribuídas pelo
     * SubscriptionObserver conforme a assinatura ativa.
     *
     * Como o id da permission some, os vínculos em `role_has_permissions` e
     * `model_has_permissions` apontando pra ela também precisam ser limpos
     * (na verdade o spatie já tem FK ON DELETE CASCADE, mas explicitamos
     * pra ficar à prova de configs custom).
     */
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $toDelete = [
            // Dashboard por role
            'access.dashboard.admin',
            'access.dashboard.mentorado',
            'access.dashboard.licenciado',

            // Capabilities de admin
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
            'access.guincho.manage',
            'access.limpa-nome.manage',

            // "Todo logado vê" — agora gateado por role:admin|mentorado|licenciado
            'access.profile.edit',
            'access.guincho.view',
            'access.limpa-nome.view',

            // Cliente (mentorado|licenciado)
            'access.minhaassinatura.view',
            'access.faturas.view',

            // Variantes antigas que podem ter ficado no DB de prod
            'access.empresas-guincho.view',
            'access.empresas-guincho.manage',
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $toDelete)
            ->where('guard_name', 'web')
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        // Limpa vínculos role↔permission
        DB::table('role_has_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        // Limpa vínculos user↔permission (usuários que receberam direto via
        // SubscriptionObserver em algum momento)
        DB::table('model_has_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        // Remove as permissions
        DB::table('permissions')
            ->whereIn('id', $permissionIds)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Não reverte. Pra "voltar" pro modelo antigo seria preciso recriar
        // todas as permissions E reatribuir aos roles — o que efetivamente
        // é o trabalho do PermissionSeeder do branch antigo. Use o git.
    }
};
