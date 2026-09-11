<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cobrança recorrente: o "molde" de uma fatura que se repete todo mês.
     *
     * Mesmo desenho já usado em despesas (template + ocorrências): aqui o molde
     * fica em `cobrancas_recorrentes` e cada mês vira uma linha em `faturas`,
     * amarrada pelo par (cobranca_recorrente_id, competencia).
     *
     * Enquanto `encerrada_em` for null, o mês corrente é sempre gerado — é isso
     * que faz a cobrança continuar aparecendo nos dados e relatórios. Ao encerrar,
     * para de gerar dali para frente; o que já foi gerado permanece no histórico.
     */
    public function up(): void
    {
        Schema::create('cobrancas_recorrentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained('processos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('descricao', 255);
            $table->decimal('valor', 12, 2);
            $table->unsignedTinyInteger('dia_vencimento');   // 1..31, ajustado em mês curto
            $table->date('data_inicio');                     // competência da 1ª cobrança
            $table->timestamp('encerrada_em')->nullable();   // null = ativa, gera todo mês
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['processo_id', 'encerrada_em']);
        });

        Schema::table('faturas', function (Blueprint $table) {
            $table->foreignId('cobranca_recorrente_id')->nullable()->after('processo_id')
                ->constrained('cobrancas_recorrentes')->nullOnDelete();
            $table->date('competencia')->nullable()->after('cobranca_recorrente_id');

            // Impede duplicar a cobrança do mesmo mês quando a geração roda de novo
            $table->unique(['cobranca_recorrente_id', 'competencia'], 'faturas_recorrencia_competencia_unique');
        });
    }

    public function down(): void
    {
        Schema::table('faturas', function (Blueprint $table) {
            $table->dropUnique('faturas_recorrencia_competencia_unique');
            $table->dropForeign(['cobranca_recorrente_id']);
            $table->dropColumn(['cobranca_recorrente_id', 'competencia']);
        });

        Schema::dropIfExists('cobrancas_recorrentes');
    }
};
