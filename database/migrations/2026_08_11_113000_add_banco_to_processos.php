<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Banco do financiamento. A taxa dele entra no cálculo do valor da parcela,
     * pelo mesmo critério da Calculadora de quitação.
     */
    public function up(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->foreignId('banco_id')->nullable()->after('servico_id')->constrained('bancos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->dropForeign(['banco_id']);
            $table->dropColumn('banco_id');
        });
    }
};
