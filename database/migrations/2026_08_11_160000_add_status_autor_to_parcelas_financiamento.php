<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Quem mexeu por último no status da parcela (pagou, reabriu, cancelou).
     * Fica na própria linha para a listagem mostrar sem consultar o histórico.
     */
    public function up(): void
    {
        Schema::table('parcelas_financiamento', function (Blueprint $table) {
            $table->foreignId('status_alterado_por_user_id')->nullable()->after('metodo')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('status_alterado_em')->nullable()->after('status_alterado_por_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('parcelas_financiamento', function (Blueprint $table) {
            $table->dropForeign(['status_alterado_por_user_id']);
            $table->dropColumn(['status_alterado_por_user_id', 'status_alterado_em']);
        });
    }
};
