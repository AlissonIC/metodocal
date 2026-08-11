<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dados de financiamento do cliente e link de pagamento mensal no processo,
     * mais os campos de contato da negociação (telefone e com quem se falou).
     */
    public function up(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->decimal('valor_financiamento', 12, 2)->nullable()->after('observacoes_admin');
            $table->unsignedSmallInteger('qtd_parcelas')->nullable()->after('valor_financiamento');
            $table->decimal('valor_parcela', 12, 2)->nullable()->after('qtd_parcelas');
            $table->string('link_pagamento_mensal', 500)->nullable()->after('valor_parcela');
        });

        Schema::table('negociacoes', function (Blueprint $table) {
            $table->string('telefone', 40)->nullable()->after('assessoria');
            $table->string('contato_nome', 120)->nullable()->after('telefone');
        });
    }

    public function down(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->dropColumn(['valor_financiamento', 'qtd_parcelas', 'valor_parcela', 'link_pagamento_mensal']);
        });

        Schema::table('negociacoes', function (Blueprint $table) {
            $table->dropColumn(['telefone', 'contato_nome']);
        });
    }
};
