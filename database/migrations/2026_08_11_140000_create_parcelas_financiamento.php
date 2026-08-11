<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Carnê do financiamento: uma linha por parcela, gerada a partir da data da
     * primeira parcela + quantidade + valor informados no processo.
     *
     * "Atrasada" não é gravada — é derivada de (pendente + vencimento no passado),
     * mesmo critério de despesa_ocorrencias e faturas.
     */
    public function up(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->date('data_primeira_parcela')->nullable()->after('valor_parcela');
        });

        Schema::create('parcelas_financiamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained('processos')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero');          // 1..qtd_parcelas
            $table->date('vencimento');
            $table->decimal('valor', 12, 2);
            $table->enum('status', ['pendente', 'paga', 'cancelada'])->default('pendente');
            $table->timestamp('pago_em')->nullable();
            $table->string('metodo', 40)->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->unique(['processo_id', 'numero']);
            $table->index(['status', 'vencimento']);
            $table->index('vencimento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcelas_financiamento');

        Schema::table('processos', function (Blueprint $table) {
            $table->dropColumn('data_primeira_parcela');
        });
    }
};
