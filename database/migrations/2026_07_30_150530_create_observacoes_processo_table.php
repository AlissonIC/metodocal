<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Observações internas (admin) do processo — múltiplos registros por processo,
     * cada um com resumo/descrição e rastro de quem inseriu e editou.
     *
     * Substitui a coluna `observacoes_admin` (texto único) que existia em `processos`;
     * a coluna é preservada por segurança e pode ser removida em migration futura.
     */
    public function up(): void
    {
        Schema::create('observacoes_processo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained('processos')->cascadeOnDelete();
            $table->foreignId('inserida_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editada_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('resumo', 200);
            $table->text('descricao');
            $table->timestamp('editada_em')->nullable();
            $table->timestamps();

            $table->index(['processo_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observacoes_processo');
    }
};
