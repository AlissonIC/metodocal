<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * despesas       → template da despesa (fixa ou única)
     * despesa_ocorrencias → instância mensal (uma linha por competência)
     *
     * Fixas geram ocorrências enquanto `encerrada_em` for null. Únicas geram
     * exatamente 1 ocorrência no cadastro.
     */
    public function up(): void
    {
        Schema::create('despesas', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 160);
            $table->string('categoria', 60)->nullable();
            $table->text('descricao')->nullable();
            $table->decimal('valor', 12, 2);
            $table->enum('tipo', ['fixa', 'unica']);
            $table->unsignedTinyInteger('dia_vencimento')->nullable(); // 1..31 — só para fixa
            $table->date('data_inicio');                                // 1º vencimento
            $table->timestamp('encerrada_em')->nullable();              // quando o usuário encerrou
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tipo', 'encerrada_em']);
        });

        Schema::create('despesa_ocorrencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('despesa_id')->constrained('despesas')->cascadeOnDelete();
            $table->date('competencia'); // primeiro dia do mês (2026-08-01)
            $table->date('vencimento');
            $table->decimal('valor', 12, 2);
            $table->enum('status', ['pendente', 'paga', 'cancelada'])->default('pendente');
            $table->timestamp('pago_em')->nullable();
            $table->string('metodo', 40)->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->unique(['despesa_id', 'competencia']);
            $table->index(['competencia', 'status']);
            $table->index('vencimento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despesa_ocorrencias');
        Schema::dropIfExists('despesas');
    }
};
