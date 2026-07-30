<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permite anexar um documento/imagem por observação.
     * Um arquivo por observação — se precisar mais, a solução é adicionar
     * novas observações (o padrão do domínio já é "múltiplas observações").
     */
    public function up(): void
    {
        Schema::table('observacoes_processo', function (Blueprint $table) {
            $table->string('anexo_arquivo')->nullable()->after('descricao');
            $table->string('anexo_nome_original')->nullable()->after('anexo_arquivo');
            $table->string('anexo_mime', 120)->nullable()->after('anexo_nome_original');
            $table->unsignedBigInteger('anexo_tamanho_bytes')->nullable()->after('anexo_mime');
        });
    }

    public function down(): void
    {
        Schema::table('observacoes_processo', function (Blueprint $table) {
            $table->dropColumn(['anexo_arquivo', 'anexo_nome_original', 'anexo_mime', 'anexo_tamanho_bytes']);
        });
    }
};
