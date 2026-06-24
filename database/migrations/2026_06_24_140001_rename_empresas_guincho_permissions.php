<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Renomeia 'access.empresas-guincho.{view,manage}' -> 'access.guincho.{view,manage}'.
     * - Se já existe com o nome novo, não faz nada.
     * - Se existe só com o nome antigo, faz UPDATE preservando o id e os
     *   vínculos com roles (model_has_permissions / role_has_permissions).
     * - Se não existir nenhum dos dois, cria com o nome novo e atribui aos
     *   roles padrão (admin, mentorado, licenciado) conforme PermissionSeeder.
     */
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $renames = [
            'access.empresas-guincho.view'   => 'access.guincho.view',
            'access.empresas-guincho.manage' => 'access.guincho.manage',
        ];

        foreach ($renames as $old => $new) {
            $hasNew = DB::table('permissions')->where('name', $new)->where('guard_name', 'web')->exists();
            $hasOld = DB::table('permissions')->where('name', $old)->where('guard_name', 'web')->exists();

            if ($hasNew) {
                // Já está com nome novo — se também existir o antigo (improvável), apaga
                if ($hasOld) {
                    DB::table('permissions')->where('name', $old)->where('guard_name', 'web')->delete();
                }
                continue;
            }

            if ($hasOld) {
                // Renomeia no lugar. Mantém id, então model_has_permissions e role_has_permissions
                // continuam apontando corretamente sem precisar migrar vínculos.
                DB::table('permissions')
                    ->where('name', $old)
                    ->where('guard_name', 'web')
                    ->update(['name' => $new]);
                continue;
            }

            // Nenhum dos dois existe — cria e atribui aos roles padrão
            $permissionId = DB::table('permissions')->insertGetId([
                'name'       => $new,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Atribui a todos os roles que historicamente tinham essa permission:
            // admin, mentorado, licenciado (conforme PermissionSeeder).
            $roleIds = DB::table('roles')
                ->whereIn('name', ['admin', 'mentorado', 'licenciado'])
                ->where('guard_name', 'web')
                ->pluck('id');

            foreach ($roleIds as $roleId) {
                $alreadyLinked = DB::table('role_has_permissions')
                    ->where('permission_id', $permissionId)
                    ->where('role_id', $roleId)
                    ->exists();
                if (!$alreadyLinked) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permissionId,
                        'role_id'       => $roleId,
                    ]);
                }
            }
        }

        // Invalida o cache do spatie/permission pra evitar "permission not found"
        // até o próximo restart de fpm/worker.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $renames = [
            'access.guincho.view'   => 'access.empresas-guincho.view',
            'access.guincho.manage' => 'access.empresas-guincho.manage',
        ];

        foreach ($renames as $new => $old) {
            DB::table('permissions')
                ->where('name', $new)
                ->where('guard_name', 'web')
                ->update(['name' => $old]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
