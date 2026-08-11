<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Nível "cliente": a pessoa titular do processo passa a poder ter login próprio.
     *
     * Até aqui ela existia só como dado dentro do processo (nome_completo/documento).
     * `cliente_user_id` é quem enxerga o processo no painel — separado de `user_id`,
     * que continua sendo o mentorado/licenciado dono da operação.
     */
    public function up(): void
    {
        Role::firstOrCreate(['name' => 'cliente', 'guard_name' => 'web']);

        Schema::table('processos', function (Blueprint $table) {
            $table->foreignId('cliente_user_id')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete();
            $table->index('cliente_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->dropForeign(['cliente_user_id']);
            $table->dropIndex(['cliente_user_id']);
            $table->dropColumn('cliente_user_id');
        });

        Role::where('name', 'cliente')->where('guard_name', 'web')->delete();
    }
};
