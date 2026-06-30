<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona `tipo` (a_receber/a_pagar) e vínculo opcional a processo nas comissões.
     * Comissões agora podem ser despesas (a_pagar para alguém externo) ou receitas
     * (a_receber, fluxo do licenciado existente).
     */
    public function up(): void
    {
        Schema::table('comissoes', function (Blueprint $table) {
            $table->enum('tipo', ['a_receber', 'a_pagar'])->default('a_receber')->after('valor');
            $table->foreignId('processo_id')
                ->nullable()
                ->after('cliente_id')
                ->constrained('processos')
                ->nullOnDelete();

            $table->index(['tipo', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('comissoes', function (Blueprint $table) {
            $table->dropIndex(['tipo', 'status']);
            $table->dropForeign(['processo_id']);
            $table->dropColumn(['tipo', 'processo_id']);
        });
    }
};
