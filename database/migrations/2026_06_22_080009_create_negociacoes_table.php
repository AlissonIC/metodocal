<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registros de negociação do processo — histórico de conversas com o banco/credor
     * contendo valores atualizados, análise da instituição e proposta em mãos.
     */
    public function up(): void
    {
        Schema::create('negociacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained('processos')->cascadeOnDelete();
            $table->foreignId('inserida_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('data');
            $table->string('assessoria', 120)->nullable();
            $table->text('resumo');
            $table->decimal('val_atualizado', 12, 2)->nullable();  // Saldo devedor atualizado
            $table->decimal('val_analise', 12, 2)->nullable();     // Valor sugerido pelo banco
            $table->decimal('val_em_maos', 12, 2)->nullable();     // Proposta apresentada
            $table->text('feedback')->nullable();                   // Retorno da negociação
            $table->timestamps();

            $table->index(['processo_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('negociacoes');
    }
};
