<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Cada Comprador sem user_id ganha uma linha de User com role 'comprador' e é
     * vinculado. Assim a lista unificada de Usuários passa a exibir 100% dos compradores.
     *
     * Emails duplicados são resolvidos anexando o id ao slug do email
     * (comprador+7@... vira comprador+7-slug@... se colidir).
     */
    public function up(): void
    {
        $roleId = DB::table('roles')->where('name', 'comprador')->value('id');
        if (! $roleId) return; // Sem role, nada a fazer

        $orfaos = DB::table('compradores')->whereNull('user_id')->get();
        if ($orfaos->isEmpty()) return;

        foreach ($orfaos as $c) {
            $email = $this->emailUnico($c);
            $userId = DB::table('users')->insertGetId([
                'name' => $c->nome ?: ('Comprador #' . $c->id),
                'email' => $email,
                'password' => Hash::make(Str::random(24)), // senha aleatória — admin pode redefinir
                'phone' => $c->telefone,
                'cpf_cnpj' => $c->documento,
                'tipo_documento' => $c->tipo_documento,
                'status' => $c->ativo ? 'ativo' : 'inativo',
                'observacoes' => $c->observacoes,
                'email_verified_at' => now(),
                'created_at' => $c->created_at ?: now(),
                'updated_at' => now(),
            ]);

            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $roleId,
                'model_type' => \App\Models\User::class,
                'model_id' => $userId,
            ]);

            DB::table('compradores')->where('id', $c->id)->update(['user_id' => $userId]);
        }
    }

    public function down(): void
    {
        // Remove apenas os users criados por esta migration (identificados pelo padrão de email).
        DB::table('users')->where('email', 'like', 'comprador+%@sistema.local')->delete();
    }

    private function emailUnico(object $c): string
    {
        $base = $c->email && filter_var($c->email, FILTER_VALIDATE_EMAIL)
            ? $c->email
            : 'comprador+' . $c->id . '@sistema.local';

        $email = $base;
        $suffix = 1;
        while (DB::table('users')->where('email', $email)->exists()) {
            [$local, $domain] = explode('@', $base, 2);
            $email = $local . '-' . $suffix . '@' . $domain;
            $suffix++;
        }
        return $email;
    }
};
